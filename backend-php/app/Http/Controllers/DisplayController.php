<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteeSession;
use App\Models\Delegate;
use App\Models\Motion;
use App\Models\SpeakerList;
use App\Models\SpeakerListEntry;
use App\Support\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DisplayController extends Controller
{
    /**
     * GET /api/display/board?committeeId=X
     * 获取大屏所需的完整状态
     */
    public function getBoard(Request $request): JsonResponse
    {
        $committeeId = $request->query->get('committeeId');
        if (!$committeeId) {
            return $this->json(['error' => 'committeeId is required'], 400);
        }

        $committee = Committee::query()->find($committeeId);
        if (!$committee) {
            return $this->json(['error' => 'Committee not found'], 404);
        }

        // 获取委员会的代表统计
        $totalDelegates = Delegate::query()
            ->where('committee_id', $committeeId)
            ->count();

        $presentDelegates = Delegate::query()
            ->where('committee_id', $committeeId)
            ->where('status', 'present')
            ->count();

        $twoThirdsMajority = (int) ceil($presentDelegates * 2 / 3);
        $halfMajority = (int) ceil($presentDelegates / 2);
        $twentyPercentMajority = (int) ceil($presentDelegates * 0.2);

        // 获取当前活跃的 CommitteeSession 及其 current_speaker_list_id
        $currentSession = CommitteeSession::query()
            ->where('committee_id', $committeeId)
            ->orderBy('created_at', 'desc')
            ->first();

        $speakerQueue = [];
        $speakerListId = null;
        $activeMotionMeta = null;
        
        // 优先使用 current_speaker_list_id，如果没有则使用最近的 motion
        if ($currentSession && $currentSession->current_speaker_list_id) {
            $speakerListId = $currentSession->current_speaker_list_id;
            $entries = SpeakerListEntry::query()
                ->where('speaker_list_id', $speakerListId)
                ->whereIn('status', ['waiting', 'speaking'])
                ->orderBy('position')
                ->with(['delegate.user'])
                ->get();

            foreach ($entries as $entry) {
                $speakerQueue[] = [
                    'id' => $entry->id,
                    'country' => $entry->delegate?->country ?? 'Unknown',
                    'delegate' => $entry->delegate?->user?->name ?? 'Unknown',
                    'status' => $entry->status,
                    'position' => $entry->position,
                ];
            }

            $activeMotion = Motion::query()
                ->where('speaker_list_id', $speakerListId)
                ->orderByDesc('created_at')
                ->first();

            if ($activeMotion) {
                $activeMotionMeta = [
                    'id' => $activeMotion->id,
                    'motionType' => $activeMotion->motion_type,
                    'unitTimeSeconds' => $activeMotion->unit_time_seconds,
                    'totalTimeSeconds' => $activeMotion->total_time_seconds,
                ];
            }
        }

        // 获取历史记录（最近的 motions）
        $historyEvents = [];
        $recentMotions = Motion::query()
            ->join('CommitteeSessions', 'Motions.committee_session_id', '=', 'CommitteeSessions.id')
            ->where('CommitteeSessions.committee_id', $committeeId)
            ->where('Motions.state', '!=', 'pending')
            ->orderBy('Motions.created_at', 'desc')
            ->limit(20)
            ->select('Motions.*')
            ->with(['proposer.user'])
            ->get();

        $motionTypeNames = [
            'open_main_list' => '开启主发言名单',
            'moderate_caucus' => '有主持核心磋商',
            'unmoderated_caucus' => '自由磋商',
            'unmoderated_debate' => '自由辩论',
            'right_of_query' => '质询权',
            'begin_special_state' => '进入特殊状态（时间轴改变）',
            'end_special_state' => '退出特殊状态（时间轴改变）',
            'adjourn_meeting' => '休会',
            'document_reading' => '阅读文件',
            'personal_speech' => '个人演讲',
            'vote' => '对文件投票',
            'right_of_reply' => '答辩权',
        ];

        foreach ($recentMotions as $motion) {
            $title = $motion->state === 'passed' ? '动议通过' : '动议未通过';
            $motionTypeCn = $motionTypeNames[$motion->motion_type] ?? $motion->motion_type;
            
            $description = '';
            $proposerInfo = '';
            if ($motion->proposer) {
                $proposerInfo = $motion->proposer->country ?? 'Unknown';
                if ($motion->proposer->user) {
                    $proposerInfo .= ' (' . $motion->proposer->user->name . ')';
                }
                $description = $proposerInfo . ' 动议了一个' . $motionTypeCn;
            } else {
                $description = $motionTypeCn;
            }
            
            if ($motion->unit_time_seconds) {
                $description .= ' · 单次 ' . $motion->unit_time_seconds . ' 秒';
            }
            
            if ($motion->total_time_seconds) {
                $description .= ' · 总时长 ' . $motion->total_time_seconds . ' 秒';
            }

            $historyEvents[] = [
                'id' => $motion->id,
                'title' => $title . '：' . $motionTypeCn,
                'description' => $description,
                'proposer' => $proposerInfo,
                'createdAt' => $motion->created_at?->toIso8601String(),
            ];
        }

        // 解析 time_config
        $timeConfig = $committee->time_config;
        if (is_string($timeConfig)) {
            $timeConfig = json_decode($timeConfig, true);
        }
        if (!is_array($timeConfig)) {
            $timeConfig = [];
        }

        // 获取总的发言列表数量和当前索引
        $allSpeakerListIds = Motion::query()
            ->join('CommitteeSessions', 'Motions.committee_session_id', '=', 'CommitteeSessions.id')
            ->where('CommitteeSessions.committee_id', $committeeId)
            ->whereNotNull('Motions.speaker_list_id')
            ->orderBy('Motions.created_at', 'asc')
            ->pluck('Motions.speaker_list_id')
            ->unique()
            ->values()
            ->toArray();
        
        $currentIndex = $speakerListId ? array_search($speakerListId, $allSpeakerListIds) : false;
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return $this->json([
            'committee' => [
                'id' => $committee->id,
                'code' => $committee->code,
                'name' => $committee->name,
                'venue' => $committee->venue,
                'status' => $committee->status,
                'timeConfig' => $timeConfig,
            ],
            'statistics' => [
                'total' => $totalDelegates,
                'present' => $presentDelegates,
                'twoThirds' => $twoThirdsMajority,
                'half' => $halfMajority,
                'twentyPercent' => $twentyPercentMajority,
            ],
            'speakerQueue' => $speakerQueue,
            'speakerListId' => $speakerListId,
            'activeMotion' => $activeMotionMeta,
            'currentIndex' => $currentIndex,
            'totalLists' => count($allSpeakerListIds),
            'historyEvents' => $historyEvents,
        ]);
    }

    /**
     * POST /api/display/speakers
     * 添加发言者到发言队列
     */
    public function addSpeaker(Request $request): JsonResponse
    {
        // 显示大屏操作不需要认证
        $data = $this->body($request);
        $speakerListId = $data['speakerListId'] ?? null;
        $delegateId = $data['delegateId'] ?? null;

        if (!$speakerListId || !$delegateId) {
            return $this->json(['error' => 'speakerListId and delegateId are required'], 400);
        }

        $speakerList = SpeakerList::query()->find($speakerListId);
        if (!$speakerList) {
            return $this->json(['error' => 'Speaker list not found'], 404);
        }

        $delegate = Delegate::query()->find($delegateId);
        if (!$delegate) {
            return $this->json(['error' => 'Delegate not found'], 404);
        }

        // 获取当前最大 position
        $maxPosition = SpeakerListEntry::query()
            ->where('speaker_list_id', $speakerListId)
            ->max('position') ?? 0;

        // 创建新的发言者条目
        $entry = new SpeakerListEntry([
            'speaker_list_id' => $speakerListId,
            'delegate_id' => $delegateId,
            'position' => $maxPosition + 1,
            'status' => 'waiting',
        ]);
        $entry->save();

        // 重新加载以获取关联的delegate数据
        $entry = SpeakerListEntry::query()
            ->with(['delegate.user'])
            ->find($entry->id);

        return $this->json([
            'entry' => $entry->toApiResponse(),
        ]);
    }

    /**
     * POST /api/display/roll-call
     * 发起点名，更新所有代表的 status
     */
    public function rollCall(Request $request): JsonResponse
    {
        // 显示大屏操作不需要认证
        $data = $this->body($request);
        $committeeId = $data['committeeId'] ?? null;
        $attendance = $data['attendance'] ?? []; // { delegateId: 'present'|'absent' }

        if (!$committeeId) {
            return $this->json(['error' => 'committeeId is required'], 400);
        }

        $committee = Committee::query()->find($committeeId);
        if (!$committee) {
            return $this->json(['error' => 'Committee not found'], 404);
        }

        // 更新代表的出席状态
        foreach ($attendance as $delegateId => $status) {
            if (in_array($status, ['present', 'absent'], true)) {
                DB::table('Delegates')
                    ->where('id', $delegateId)
                    ->where('committee_id', $committeeId)
                    ->update(['status' => $status]);
            }
        }

        // 返回更新后的统计
        $totalDelegates = Delegate::query()
            ->where('committee_id', $committeeId)
            ->count();

        $presentDelegates = Delegate::query()
            ->where('committee_id', $committeeId)
            ->where('status', 'present')
            ->count();

        return $this->json([
            'total' => $totalDelegates,
            'present' => $presentDelegates,
            'twoThirds' => (int) ceil($presentDelegates * 2 / 3),
            'half' => (int) ceil($presentDelegates / 2),
            'twentyPercent' => (int) ceil($presentDelegates * 0.2),
        ]);
    }

    /**
     * POST /api/display/start-session
     * 开始会议，将 committee status 从 preparation/paused 改为 in_session
     */
    public function startSession(Request $request): JsonResponse
    {
        // 显示大屏操作不需要认证
        $data = $this->body($request);
        $committeeId = $data['committeeId'] ?? null;

        if (!$committeeId) {
            return $this->json(['error' => 'committeeId is required'], 400);
        }

        $committee = Committee::query()->find($committeeId);
        if (!$committee) {
            return $this->json(['error' => 'Committee not found'], 404);
        }

        if (!in_array($committee->status, ['preparation', 'paused'], true)) {
            return $this->json(['error' => 'Committee must be in preparation or paused status'], 400);
        }

        $committee->status = 'in_session';
        $committee->save();

        return $this->json([
            'committee' => [
                'id' => $committee->id,
                'status' => $committee->status,
            ],
        ]);
    }

    /**
     * GET /api/display/sessions?committeeId=X
     * 获取委员会的会议历程（CommitteeSessions和相关的Motions）
     */
    public function getSessions(Request $request): JsonResponse
    {
        $committeeId = $request->query->get('committeeId');
        if (!$committeeId) {
            return $this->json(['error' => 'committeeId is required'], 400);
        }

        $committee = Committee::query()->find($committeeId);
        if (!$committee) {
            return $this->json(['error' => 'Committee not found'], 404);
        }

        // 获取该委员会的所有 motions（历程），按时间倒序
        $motions = Motion::query()
            ->join('CommitteeSessions', 'Motions.committee_session_id', '=', 'CommitteeSessions.id')
            ->where('CommitteeSessions.committee_id', $committeeId)
            ->orderBy('Motions.created_at', 'desc')
            ->select('Motions.*')
            ->with(['proposer.user', 'committeeSession'])
            ->get();

        $motionTypeNames = [
            'open_main_list' => '开启主发言名单',
            'moderate_caucus' => '有主持核心磋商',
            'unmoderated_caucus' => '自由磋商',
            'unmoderated_debate' => '自由辩论',
            'right_of_query' => '质询权',
            'begin_special_state' => '进入特殊状态（时间轴改变）',
            'end_special_state' => '退出特殊状态（时间轴改变）',
            'adjourn_meeting' => '休会',
            'document_reading' => '阅读文件',
            'personal_speech' => '个人演讲',
            'vote' => '对文件投票',
            'right_of_reply' => '答辩权',
        ];

        $items = [];
        foreach ($motions as $motion) {
            $items[] = [
                'id' => $motion->id,
                'committeeSessionId' => $motion->committee_session_id,
                'motionType' => $motion->motion_type,
                'motionTypeName' => $motionTypeNames[$motion->motion_type] ?? $motion->motion_type,
                'unitTimeSeconds' => $motion->unit_time_seconds,
                'totalTimeSeconds' => $motion->total_time_seconds,
                'proposerId' => $motion->proposer_id,
                'proposerName' => $motion->proposer?->user?->name ?? null,
                'proposerCountry' => $motion->proposer?->country ?? null,
                'state' => $motion->state,
                'voteResult' => $motion->vote_result,
                'speakerListId' => $motion->speaker_list_id,
                'createdAt' => $motion->created_at?->toIso8601String(),
                'updatedAt' => $motion->updated_at?->toIso8601String(),
            ];
        }

        return $this->json([
            'items' => $items,
            'total' => count($items),
        ]);
    }

    /**
     * POST /api/display/switch-speaker-list
     * 切换当前发言列表
     */
    public function switchSpeakerList(Request $request): JsonResponse
    {
        // 显示大屏操作不需要认证
        $data = $this->body($request);
        $committeeId = $data['committeeId'] ?? null;
        $direction = $data['direction'] ?? null; // 'prev' or 'next'
        $deleteEmptyList = $data['deleteEmptyList'] ?? null; // 要删除的空列表ID

        if (!$committeeId || !$direction) {
            return $this->json(['error' => 'committeeId and direction are required'], 400);
        }

        if (!in_array($direction, ['prev', 'next'], true)) {
            return $this->json(['error' => 'direction must be prev or next'], 400);
        }

        // 如果需要删除空列表
        if ($deleteEmptyList) {
            // 检查该列表是否真的为空
            $entryCount = SpeakerListEntry::query()
                ->where('speaker_list_id', $deleteEmptyList)
                ->whereIn('status', ['waiting', 'speaking'])
                ->count();
            
            if ($entryCount === 0) {
                // 删除发言列表
                SpeakerList::query()->where('id', $deleteEmptyList)->delete();
                
                // 删除关联的motion的speaker_list_id引用
                Motion::query()
                    ->where('speaker_list_id', $deleteEmptyList)
                    ->update(['speaker_list_id' => null]);
            }
        }

        // 获取当前活跃的 CommitteeSession
        $currentSession = CommitteeSession::query()
            ->where('committee_id', $committeeId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentSession) {
            return $this->json(['error' => 'No active session found'], 404);
        }

        // 获取该委员会所有有 speaker_list_id 的 motions
        $motions = Motion::query()
            ->join('CommitteeSessions', 'Motions.committee_session_id', '=', 'CommitteeSessions.id')
            ->where('CommitteeSessions.committee_id', $committeeId)
            ->whereNotNull('Motions.speaker_list_id')
            ->orderBy('Motions.created_at', 'asc')
            ->select('Motions.*')
            ->get();

        if ($motions->isEmpty()) {
            return $this->json(['error' => 'No speaker lists available'], 404);
        }

        $currentSpeakerListId = $currentSession->current_speaker_list_id;
        $speakerListIds = $motions->pluck('speaker_list_id')->unique()->values()->toArray();

        // 查找当前列表的索引
        $currentIndex = array_search($currentSpeakerListId, $speakerListIds);
        if ($currentIndex === false) {
            // 如果没有当前列表，默认使用第一个
            $currentIndex = 0;
        }

        // 计算新的索引
        if ($direction === 'next') {
            $newIndex = ($currentIndex + 1) % count($speakerListIds);
        } else {
            $newIndex = ($currentIndex - 1 + count($speakerListIds)) % count($speakerListIds);
        }

        $newSpeakerListId = $speakerListIds[$newIndex];

        // 更新 current_speaker_list_id
        $currentSession->current_speaker_list_id = $newSpeakerListId;
        $currentSession->save();

        return $this->json([
            'success' => true,
            'speakerListId' => $newSpeakerListId,
            'currentIndex' => $newIndex,
            'totalLists' => count($speakerListIds),
        ]);
    }

    /**
     * POST /api/display/timer/start
     * 开始计时
     */
    public function startTimer(Request $request): JsonResponse
    {
        $data = $this->body($request);
        $speakerListId = $data['speakerListId'] ?? null;

        if (!$speakerListId) {
            return $this->json(['error' => 'speakerListId is required'], 400);
        }

        // 获取当前正在等待的第一个发言者
        $currentSpeaker = SpeakerListEntry::query()
            ->where('speaker_list_id', $speakerListId)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->first();

        if (!$currentSpeaker) {
            return $this->json(['error' => 'No waiting speakers found'], 404);
        }

        // 更新状态为 speaking
        $currentSpeaker->status = 'speaking';
        $currentSpeaker->save();

        return $this->json([
            'success' => true,
            'currentSpeaker' => [
                'id' => $currentSpeaker->id,
                'position' => $currentSpeaker->position,
                'status' => $currentSpeaker->status,
            ],
        ]);
    }

    /**
     * POST /api/display/timer/stop
     * 停止计时
     */
    public function stopTimer(Request $request): JsonResponse
    {
        $data = $this->body($request);
        $speakerListId = $data['speakerListId'] ?? null;

        if (!$speakerListId) {
            return $this->json(['error' => 'speakerListId is required'], 400);
        }

        // 获取当前正在发言的人
        $currentSpeaker = SpeakerListEntry::query()
            ->where('speaker_list_id', $speakerListId)
            ->where('status', 'speaking')
            ->first();

        if ($currentSpeaker) {
            // 恢复为 waiting 状态
            $currentSpeaker->status = 'waiting';
            $currentSpeaker->save();
        }

        return $this->json([
            'success' => true,
        ]);
    }

    /**
     * POST /api/display/speaker/next
     * 下一个发言者 - 删除当前发言者并调整后续position
     */
    public function nextSpeaker(Request $request): JsonResponse
    {
        $data = $this->body($request);
        $speakerListId = $data['speakerListId'] ?? null;

        if (!$speakerListId) {
            return $this->json(['error' => 'speakerListId is required'], 400);
        }

        DB::beginTransaction();
        try {
            // 找到当前正在发言的人
            $currentSpeaker = SpeakerListEntry::query()
                ->where('speaker_list_id', $speakerListId)
                ->where('status', 'speaking')
                ->first();

            // 如果没有正在发言的人，找第一个waiting的
            if (!$currentSpeaker) {
                $currentSpeaker = SpeakerListEntry::query()
                    ->where('speaker_list_id', $speakerListId)
                    ->where('status', 'waiting')
                    ->orderBy('position')
                    ->first();
            }

            if (!$currentSpeaker) {
                DB::rollBack();
                return $this->json(['error' => 'No current speaker found'], 404);
            }

            $currentPosition = $currentSpeaker->position;

            // 删除当前发言者
            $currentSpeaker->delete();

            // 调整后续所有人的 position（-1）
            DB::table('SpeakerListEntries')
                ->where('speaker_list_id', $speakerListId)
                ->where('position', '>', $currentPosition)
                ->decrement('position');

            DB::commit();

            // 获取更新后的发言队列
            $updatedQueue = SpeakerListEntry::query()
                ->where('speaker_list_id', $speakerListId)
                ->whereIn('status', ['waiting', 'speaking'])
                ->orderBy('position')
                ->with(['delegate.user'])
                ->get();

            $speakerQueue = [];
            foreach ($updatedQueue as $entry) {
                $speakerQueue[] = [
                    'id' => $entry->id,
                    'country' => $entry->delegate?->country ?? 'Unknown',
                    'delegate' => $entry->delegate?->user?->name ?? 'Unknown',
                    'status' => $entry->status,
                    'position' => $entry->position,
                ];
            }

            return $this->json([
                'success' => true,
                'speakerQueue' => $speakerQueue,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->json(['error' => 'Failed to move to next speaker: ' . $e->getMessage()], 500);
        }
    }
}
