<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Committee;
use App\Models\Delegate;
use App\Models\Message;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);
        $context = $this->determineAccessContext($user);

        $query = $this->queryForUser($user, $context)->with(['sender', 'committee']);

        $targetFilter = (string) $request->query->get('target');
        if ($targetFilter !== '' && $targetFilter !== 'all') {
            $query->where('target', $targetFilter);
        }

        $committeeFilter = (int) $request->query->get('committeeId', 0);
        if ($committeeFilter > 0) {
            $query->where('committee_id', $committeeFilter);
        }

        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $query->where('content', 'LIKE', '%' . addcslashes($search, '%_') . '%');
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->query->get('pageSize', 20)));

        $total = (clone $query)->count();
        $messages = $query
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get();

        $targetMeta = $this->buildTargetMeta($messages);
        $items = $messages->map(fn (Message $message) => $message->toApiResponse($targetMeta[$message->id] ?? null))->all();

        return $this->json([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'recipients' => $this->buildRecipientPayload($context),
            'allowedTargets' => $this->allowedTargets($context),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);
        $context = $this->determineAccessContext($user);

        $payload = $this->body($request);
        $target = (string) ($payload['target'] ?? 'everyone');
        $content = trim((string) ($payload['content'] ?? ''));
        $targetId = isset($payload['targetId']) ? (int) $payload['targetId'] : null;
        $committeeId = isset($payload['committeeId']) ? (int) $payload['committeeId'] : null;

        if ($content === '') {
            throw new HttpException('消息内容不能为空', 400);
        }
        if (mb_strlen($content) > 2000) {
            throw new HttpException('消息内容请限制在 2000 字符内', 400);
        }

        $allowedTargets = $this->allowedTargets($context);
        if (!in_array($target, $allowedTargets, true)) {
            throw new HttpException('无权向该目标发送消息', 403);
        }

        $resolvedCommitteeId = null;
        $resolvedTargetId = $targetId;

        switch ($target) {
            case 'everyone':
                if ($context['role'] === 'delegate') {
                    throw new HttpException('代表无法广播至全体', 403);
                }
                $resolvedTargetId = null;
                break;
            case 'committee':
                if (!$targetId) {
                    throw new HttpException('请选择具体会场', 400);
                }
                $this->assertCommitteeAccess($targetId, $context);
                $resolvedCommitteeId = $targetId;
                break;
            case 'dias':
                if ($targetId) {
                    $this->assertCommitteeAccess($targetId, $context);
                    $resolvedCommitteeId = $targetId;
                } elseif (($context['role'] ?? null) === 'delegate' && !empty($context['delegateCommittees'])) {
                    // For delegates, use their first committee if not specified
                    $resolvedCommitteeId = $context['delegateCommittees'][0];
                } else {
                    throw new HttpException('请选择主席团对应的会场', 400);
                }
                break;
            case 'delegate':
                if (!$targetId) {
                    throw new HttpException('请选择具体代表', 400);
                }
                if (!in_array($context['role'], ['admin', 'presidium'], true)) {
                    throw new HttpException('仅主席团或管理员可私信代表', 403);
                }
                [$delegateRecord, $resolvedCommitteeId] = $this->resolveDelegateTarget($targetId, $committeeId);
                $this->assertCommitteeAccess($resolvedCommitteeId, $context);
                $resolvedTargetId = $delegateRecord->user_id;
                break;
            default:
                throw new HttpException('不支持的 target 类型', 400);
        }

        $message = new Message();
        $message->time = Carbon::now('UTC');
        $message->from_user_id = $user->id;
        $message->target_id = $resolvedTargetId;
        $message->channel = $this->channelForTarget($target);
        $message->target = $target;
        $message->committee_id = $resolvedCommitteeId;
        $message->content = $content;
        $message->save();

        $message->load(['sender', 'committee']);
        $meta = $this->buildTargetMeta(new EloquentCollection([$message]));

        return $this->json([
            'message' => $message->toApiResponse($meta[$message->id] ?? null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function determineAccessContext(User $user): array
    {
        if ($user->role === 'observer') {
            throw new HttpException('观察员无法访问消息模块', 403);
        }

        $permissions = $user->effectivePermissions();

        if ($user->role === 'admin') {
            return ['role' => 'admin', 'permissions' => $permissions];
        }

        if (in_array('messages:broadcast', $permissions, true)) {
            $committees = $this->committeesForPresidium($user);
            $delegateUserIds = $committees
                ? Delegate::query()->whereIn('committee_id', $committees)->pluck('user_id')->unique()->values()->all()
                : [];

            return [
                'role' => 'presidium',
                'permissions' => $permissions,
                'presidiumCommittees' => $committees,
                'presidiumDelegateUserIds' => $delegateUserIds,
            ];
        }

        if (in_array('messages:send', $permissions, true)) {
            $assignments = Delegate::query()->where('user_id', $user->id)->get();
            return [
                'role' => 'delegate',
                'permissions' => $permissions,
                'delegateCommittees' => $assignments->pluck('committee_id')->unique()->values()->all(),
            ];
        }

        throw new HttpException('当前账号未获消息模块权限', 403);
    }

    private function queryForUser(User $user, array $context): Builder
    {
        if (($context['role'] ?? null) === 'admin') {
            return Message::query();
        }

        $query = Message::query();

        $query->where(function (Builder $builder) use ($user, $context) {
            $builder->where('target', 'everyone')
                ->orWhere('from_user_id', $user->id);

            if (($context['role'] ?? null) === 'delegate') {
                $builder->orWhere(function (Builder $sub) use ($user) {
                    $sub->where('target', 'delegate')->where('target_id', $user->id);
                });

                $committees = $context['delegateCommittees'] ?? [];
                if ($committees) {
                    $builder->orWhere(function (Builder $sub) use ($committees) {
                        $sub->where('target', 'committee')->whereIn('target_id', $committees);
                    });
                }
                return;
            }

            if (($context['role'] ?? null) === 'presidium') {
                $delegateIds = $context['presidiumDelegateUserIds'] ?? [];
                if ($delegateIds) {
                    $builder->orWhere(function (Builder $sub) use ($delegateIds) {
                        $sub->where('target', 'delegate')->whereIn('target_id', $delegateIds);
                    });
                }

                $committees = $context['presidiumCommittees'] ?? [];
                if ($committees) {
                    $builder->orWhere(function (Builder $sub) use ($committees) {
                        $sub->whereIn('target', ['committee', 'dias'])->whereIn('target_id', $committees);
                    });
                }
            }
        });

        return $query;
    }

    private function committeesForPresidium(User $user): array
    {
        return Committee::query()
            ->whereRaw("JSON_CONTAINS(COALESCE(dais_json, '[]'), JSON_OBJECT('id', ?))", [$user->id])
            ->pluck('id')
            ->all();
    }

    private function buildRecipientPayload(array $context): array
    {
        $role = $context['role'] ?? 'delegate';

        if ($role === 'admin') {
            $committees = Committee::query()->orderBy('name')->get(['id', 'name', 'code']);
            $delegates = Delegate::query()->with('user')->orderBy('committee_id')->get();
        } elseif ($role === 'presidium') {
            $committeeIds = $context['presidiumCommittees'] ?? [];
            $committees = $committeeIds
                ? Committee::query()->whereIn('id', $committeeIds)->orderBy('name')->get(['id', 'name', 'code'])
                : new EloquentCollection();
            $delegates = $committeeIds
                ? Delegate::query()->with('user')->whereIn('committee_id', $committeeIds)->orderBy('committee_id')->get()
                : new EloquentCollection();
        } else {
            $committeeIds = $context['delegateCommittees'] ?? [];
            $committees = $committeeIds
                ? Committee::query()->whereIn('id', $committeeIds)->orderBy('name')->get(['id', 'name', 'code'])
                : new EloquentCollection();
            $delegates = new EloquentCollection();
        }

        $committeeMap = $committees->keyBy('id');

        return [
            'committees' => $committees->map(fn (Committee $committee) => [
                'id' => $committee->id,
                'name' => $committee->name,
                'code' => $committee->code,
            ])->values()->all(),
            'delegates' => $delegates->map(fn (Delegate $delegate) => [
                'delegateId' => $delegate->id,
                'userId' => $delegate->user_id,
                'name' => $delegate->user?->name ?? '未命名代表',
                'committeeId' => $delegate->committee_id,
                'committeeName' => $committeeMap->get($delegate->committee_id)?->name,
                'country' => $delegate->country,
            ])->values()->all(),
        ];
    }

    private function allowedTargets(array $context): array
    {
        return match ($context['role'] ?? 'delegate') {
            'admin' => ['everyone', 'committee', 'dias', 'delegate'],
            'presidium' => ['everyone', 'committee', 'dias', 'delegate'],
            'delegate' => ['committee', 'dias'],
            default => ['dias'],
        };
    }

    private function buildTargetMeta(EloquentCollection $messages): array
    {
        $delegateIds = $messages
            ->where('target', 'delegate')
            ->pluck('target_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $committeeIds = $messages
            ->whereIn('target', ['committee', 'dias'])
            ->pluck('target_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $delegateUsers = $delegateIds
            ? User::query()->whereIn('id', $delegateIds)->get(['id', 'name'])->keyBy('id')
            : new SupportCollection();

        $committeeMap = $committeeIds
            ? Committee::query()->whereIn('id', $committeeIds)->get(['id', 'name', 'code'])->keyBy('id')
            : new SupportCollection();

        $meta = [];
        foreach ($messages as $message) {
            switch ($message->target) {
                case 'delegate':
                    $user = $delegateUsers->get($message->target_id);
                    $meta[$message->id] = [
                        'label' => '代表私聊',
                        'recipientName' => $user->name ?? '未知代表',
                    ];
                    break;
                case 'committee':
                    $committee = $committeeMap->get($message->target_id);
                    $meta[$message->id] = [
                        'label' => '会场广播',
                        'committeeName' => $committee->name ?? '未知会场',
                        'committeeCode' => $committee->code ?? null,
                    ];
                    break;
                case 'dias':
                    $committee = $committeeMap->get($message->target_id);
                    $meta[$message->id] = [
                        'label' => '主席团通道',
                        'committeeName' => $committee->name ?? '未知会场',
                    ];
                    break;
                default:
                    $meta[$message->id] = [
                        'label' => '全体广播',
                    ];
            }
        }

        return $meta;
    }

    private function channelForTarget(string $target): string
    {
        return match ($target) {
            'delegate' => 'private',
            'committee' => 'committee',
            'dias' => 'dais',
            default => 'global',
        };
    }

    /**
     * @return array{0: Delegate, 1: int}
     */
    private function resolveDelegateTarget(int $userId, ?int $committeeId = null): array
    {
        $query = Delegate::query()->where('user_id', $userId);
        if ($committeeId) {
            $query->where('committee_id', $committeeId);
        }

        $delegates = $query->get();
        if ($delegates->isEmpty()) {
            throw new HttpException('未找到指定代表', 404);
        }

        if ($delegates->count() > 1 && !$committeeId) {
            throw new HttpException('该代表属于多个会场，请指定 committeeId', 400);
        }

        $delegate = $delegates->first();
        return [$delegate, (int) $delegate->committee_id];
    }

    private function assertCommitteeAccess(int $committeeId, array $context): void
    {
        if (($context['role'] ?? null) === 'admin') {
            return;
        }

        $allowedCommittees = match ($context['role'] ?? 'delegate') {
            'presidium' => $context['presidiumCommittees'] ?? [],
            'delegate' => $context['delegateCommittees'] ?? [],
            default => [],
        };

        if (!in_array($committeeId, $allowedCommittees, true)) {
            throw new HttpException('无权操作该会场', 403);
        }
    }
}