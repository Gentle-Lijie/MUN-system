<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteeSession;
use App\Models\Delegate;
use App\Models\Motion;
use App\Models\SpeakerList;
use App\Models\SpeakerListEntry;
use App\Support\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MotionController extends Controller
{
    /**
     * POST /api/motions
     * 记录动议投票结果及关联文件
     */
    public function create(Request $request): JsonResponse
    {
        $user = Auth::user($this->app, $request, false);
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = $this->body($request);

        $committeeSessionId = $data['committeeSessionId'] ?? null;
        $motionType = $data['motionType'] ?? null;
        $proposerId = $data['proposerId'] ?? null;
        $fileId = $data['fileId'] ?? null;
        $unitTimeSeconds = $data['unitTimeSeconds'] ?? null;
        $totalTimeSeconds = $data['totalTimeSeconds'] ?? null;
        $voteRequired = $data['voteRequired'] ?? false;
        $vetoApplicable = $data['vetoApplicable'] ?? false;
        $state = $data['state'] ?? 'pending';
        $voteResult = $data['voteResult'] ?? null;
        $committeeId = $data['committeeId'] ?? null;
        $description = $data['description'] ?? null;

        if (!$motionType) {
            return $this->json(['error' => 'motionType is required'], 400);
        }

        // 如果没有提供 committeeSessionId，但提供了 committeeId，需要获取或创建一个CommitteeSession
        if (!$committeeSessionId && $committeeId) {
            $committee = Committee::query()->find($committeeId);
            if (!$committee) {
                return $this->json(['error' => 'Committee not found'], 404);
            }

            // 获取或创建当前活跃的 CommitteeSession
            $committeeSession = CommitteeSession::query()
                ->where('committee_id', $committeeId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$committeeSession) {
                // 创建一个默认的 CommitteeSession
                $committeeSession = new CommitteeSession([
                    'committee_id' => $committeeId,
                    'topic' => '默认会期',
                    'chair' => null,
                    'start_time' => date('Y-m-d H:i:s'),
                    'duration_minutes' => 120,
                ]);
                $committeeSession->save();
            }

            $committeeSessionId = $committeeSession->id;
        }

        if (!$committeeSessionId) {
            return $this->json(['error' => 'committeeSessionId or committeeId is required'], 400);
        }

        $speakerListId = null;

        // 对于开启主发言名单或有主持核心磋商，创建 speakerList
        if (in_array($motionType, ['open_main_list', 'moderate_caucus'], true)) {
            $committeeSession = CommitteeSession::query()->find($committeeSessionId);
            if ($committeeSession) {
                $speakerList = new SpeakerList([
                    'committee_id' => $committeeSession->committee_id,
                ]);
                $speakerList->save();
                $speakerListId = $speakerList->id;

                // 更新 CommitteeSession 的 current_speaker_list_id
                $committeeSession->current_speaker_list_id = $speakerListId;
                $committeeSession->save();
            }
        }

        // 创建 motion
        $motion = new Motion([
            'committee_session_id' => $committeeSessionId,
            'motion_type' => $motionType,
            'proposer_id' => $proposerId,
            'file_id' => $fileId,
            'unit_time_seconds' => $unitTimeSeconds,
            'total_time_seconds' => $totalTimeSeconds,
            'speaker_list_id' => $speakerListId,
            'vote_required' => $voteRequired,
            'veto_applicable' => $vetoApplicable,
            'state' => $state,
            'vote_result' => $voteResult,
            'description' => $description,
        ]);
        $motion->save();

        return $this->json([
            'motion' => $motion->toApiResponse(),
        ]);
    }

    /**
     * POST /api/motions/{motionId}/{listId}
     * 更新 speakerList 的发言顺序和状态
     */
    public function updateSpeakerList(Request $request, array $params): JsonResponse
    {
        $user = Auth::user($this->app, $request, false);
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $motion = Motion::query()->find((int) $params['motionId']);
        if (!$motion) {
            return $this->json(['error' => 'Motion not found'], 404);
        }

        $speakerList = SpeakerList::query()->find((int) $params['listId']);
        if (!$speakerList) {
            return $this->json(['error' => 'Speaker list not found'], 404);
        }

        if ($motion->speaker_list_id !== (int) $params['listId']) {
            return $this->json(['error' => 'Speaker list does not belong to this motion'], 400);
        }

        $data = $this->body($request);
        $entries = $data['entries'] ?? [];

        // 更新发言者条目
        foreach ($entries as $entryData) {
            $entryId = $entryData['id'] ?? null;
            $position = $entryData['position'] ?? null;
            $status = $entryData['status'] ?? null;

            if ($entryId) {
                $entry = SpeakerListEntry::query()->find($entryId);
                if ($entry && $entry->speaker_list_id === (int) $params['listId']) {
                    if ($position !== null) {
                        $entry->position = $position;
                    }
                    if ($status !== null && in_array($status, ['waiting', 'speaking', 'removed'], true)) {
                        $entry->status = $status;
                    }
                    $entry->save();
                }
            }
        }

        // 重新加载 speakerList 和其条目
        $speakerList = SpeakerList::query()->with('entries')->find((int) $params['listId']);

        return $this->json([
            'speakerList' => $speakerList->toApiResponse(),
        ]);
    }
}
