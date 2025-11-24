<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Crisis;
use App\Models\CrisisResponse;
use App\Models\Delegate;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CrisisController extends Controller
{
    private const STATUSES = ['draft', 'active', 'resolved', 'archived'];

    public function index(Request $request): JsonResponse
    {
        $viewer = AuthSupport::user($this->app, $request, true);
        $query = Crisis::query()->with('publisher')->withCount('responses');

        $statusFilter = (string) $request->query->get('status', '');
        if ($statusFilter !== '') {
            $parts = array_filter(array_map('trim', explode(',', $statusFilter)));
            $valid = array_values(array_intersect($parts, self::STATUSES));
            if (!empty($valid)) {
                $query->whereIn('status', $valid);
            }
        }

        $committeeId = null;
        if (!$this->userHasPresidiumPrivileges($viewer)) {
            $committeeId = $this->getUserCommitteeId($viewer);
            $query->where(static function (Builder $builder) use ($committeeId): void {
                $builder->whereNull('target_committees');
                if ($committeeId !== null) {
                    $builder->orWhereRaw('JSON_CONTAINS(COALESCE(target_committees, JSON_ARRAY()), ?)', [json_encode((int) $committeeId)]);
                }
            });
            // Delegates don't need drafts/archived entries
            $query->whereIn('status', ['active', 'resolved']);
        }

        $crises = $query->orderByDesc('published_at')->get();
        $responsesByCrisis = [];
        if (!$this->userHasPresidiumPrivileges($viewer) && $crises->isNotEmpty()) {
            $responses = CrisisResponse::query()
                ->with(['user.delegates.committee'])
                ->where('user_id', $viewer->id)
                ->whereIn('crisis_id', $crises->pluck('id')->all())
                ->get();
            foreach ($responses as $response) {
                $responsesByCrisis[$response->crisis_id] = $response;
            }
        }

        $items = $crises->map(function (Crisis $crisis) use ($viewer, $committeeId, $responsesByCrisis) {
            $myResponse = $responsesByCrisis[$crisis->id] ?? null;
            return $this->crisisToApiResponse($crisis, $viewer, $committeeId, $myResponse);
        })->all();

        return $this->json([
            'items' => $items,
            'total' => count($items),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $publisher = AuthSupport::requirePresidium($this->app, $request);
        $body = $this->body($request);
        $data = $this->validateCrisisPayload($body, true);

        $crisis = Crisis::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'file_path' => $data['file_path'] ?? null,
            'target_committees' => $data['target_committees'] ?? null,
            'status' => $data['status'] ?? 'active',
            'responses_allowed' => $data['responses_allowed'] ?? true,
            'published_by' => $publisher->id,
            'published_at' => Carbon::now('UTC'),
        ]);
        $crisis->loadMissing('publisher');

        return $this->json($this->crisisToApiResponse($crisis), 201);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $crisis = Crisis::findOrFail((int) $params['crisisId']);
        $body = $this->body($request);
        $data = $this->validateCrisisPayload($body, false);

        if (!empty($data)) {
            $crisis->update($data);
            $crisis->refresh();
        }
        $crisis->loadMissing('publisher');

        return $this->json($this->crisisToApiResponse($crisis));
    }

    public function responses(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $crisis = Crisis::findOrFail((int) $params['crisisId']);
        $responses = CrisisResponse::query()
            ->with(['user.delegates.committee'])
            ->where('crisis_id', $crisis->id)
            ->orderByDesc('created_at')
            ->get();

        $items = $responses->map(function (CrisisResponse $response) {
            return $this->responseToArray($response);
        })->toArray();

        return $this->json([
            'crisis' => $this->crisisToApiResponse($crisis),
            'items' => $items,
            'total' => count($items),
        ]);
    }

    public function exportResponses(Request $request, array $params): Response
    {
        AuthSupport::requirePresidium($this->app, $request);
        $crisis = Crisis::findOrFail((int) $params['crisisId']);
        $responses = CrisisResponse::query()
            ->with(['user.delegates.committee'])
            ->where('crisis_id', $crisis->id)
            ->orderByDesc('created_at')
            ->get();

        $csv = "代表姓名,委员会,国家,反馈,提交时间\n";
        foreach ($responses as $response) {
            $user = $response->user;
            $delegate = $user?->delegates->first();
            $committee = $delegate?->committee;
            $content = $response->content ?? [];
            $summary = $content['summary'] ?? '';
            $createdAt = $response->created_at ? $response->created_at->toDateTimeString() : '';

            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $user?->name ?? '',
                $committee?->name ?? '',
                $delegate?->country ?? '',
                str_replace('"', '""', $summary),
                $createdAt
            );
        }

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="crisis_responses_' . $crisis->id . '.csv"',
        ]);
    }

    public function storeResponse(Request $request, array $params): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);
        $crisis = Crisis::findOrFail((int) $params['crisisId']);

        if (!$crisis->responses_allowed || $crisis->status !== 'active') {
            throw new HttpException('当前危机未开放反馈通道', 400);
        }

        $committeeId = $this->getUserCommitteeId($user);
        if (!$this->userHasPresidiumPrivileges($user) && !$this->crisisAcceptsCommittee($crisis, $committeeId)) {
            throw new HttpException('该危机未面向当前委员会', 403);
        }

        $payload = $this->validateResponsePayload($this->body($request));

        $response = CrisisResponse::updateOrCreate(
            [
                'crisis_id' => $crisis->id,
                'user_id' => $user->id,
            ],
            [
                'content' => [
                    'summary' => $payload['summary'],
                ],
                'file_path' => $payload['file_path'] ?? null,
            ]
        );
        $wasNew = $response->wasRecentlyCreated;
        $response->refresh();
        $response->loadMissing(['user.delegates.committee']);

        $status = $wasNew ? 201 : 200;
        return $this->json($this->responseToArray($response), $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function crisisToApiResponse(Crisis $crisis, ?User $viewer = null, ?int $committeeId = null, ?CrisisResponse $myResponse = null): array
    {
        $targets = $crisis->target_committees;
        $publisher = $crisis->publisher;
        $response = [
            'id' => $crisis->id,
            'title' => $crisis->title,
            'content' => $crisis->content,
            'filePath' => $crisis->file_path,
            'status' => $crisis->status,
            'responsesAllowed' => (bool) $crisis->responses_allowed,
            'targetCommittees' => $targets === null ? null : array_map('intval', (array) $targets),
            'publishedAt' => $crisis->published_at ? $crisis->published_at->toIso8601String() : null,
            'publishedBy' => $publisher ? [
                'id' => $publisher->id,
                'name' => $publisher->name,
            ] : null,
            'responsesCount' => property_exists($crisis, 'responses_count') ? (int) $crisis->responses_count : $crisis->responses()->count(),
        ];

        if ($viewer) {
            $response['canRespond'] = (bool) $crisis->responses_allowed;
        }

        if ($myResponse) {
            $response['myResponse'] = $this->responseToArray($myResponse);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseToArray(CrisisResponse $response): array
    {
        $user = $response->user;
        $delegate = $user?->delegates->first();
        $committee = $delegate?->committee;
        $content = $response->content ?? [];

        return [
            'id' => $response->id,
            'crisisId' => $response->crisis_id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ] : null,
            'committee' => $committee ? [
                'id' => $committee->id,
                'name' => $committee->name,
                'code' => $committee->code,
            ] : null,
            'country' => $delegate?->country,
            'content' => [
                'summary' => $content['summary'] ?? null,
            ],
            'filePath' => $response->file_path,
            'createdAt' => $response->created_at ? $response->created_at->toIso8601String() : null,
            'updatedAt' => $response->updated_at ? $response->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCrisisPayload(array $body, bool $isCreate): array
    {
        $data = [];
        if ($isCreate || array_key_exists('title', $body)) {
            if (!isset($body['title']) || !is_string($body['title']) || trim($body['title']) === '') {
                throw new HttpException('危机标题不能为空', 400);
            }
            if (mb_strlen($body['title']) > 255) {
                throw new HttpException('危机标题长度需小于255字符', 400);
            }
            $data['title'] = trim($body['title']);
        }

        if ($isCreate || array_key_exists('content', $body)) {
            if (!isset($body['content']) || !is_string($body['content']) || trim($body['content']) === '') {
                throw new HttpException('危机描述不能为空', 400);
            }
            $data['content'] = trim($body['content']);
        }

        if (array_key_exists('file_path', $body)) {
            if ($body['file_path'] !== null && (!is_string($body['file_path']) || mb_strlen($body['file_path']) > 500)) {
                throw new HttpException('附件地址需为500字符以内的字符串', 400);
            }
            $data['file_path'] = $body['file_path'] ?: null;
        }

        if (array_key_exists('responses_allowed', $body)) {
            $data['responses_allowed'] = (bool) $body['responses_allowed'];
        } elseif ($isCreate) {
            $data['responses_allowed'] = false;
        }

        if (array_key_exists('status', $body)) {
            if (!in_array($body['status'], self::STATUSES, true)) {
                throw new HttpException('状态取值无效', 400);
            }
            $data['status'] = $body['status'];
        } elseif ($isCreate) {
            $data['status'] = 'active';
        }

        if (array_key_exists('target_committees', $body)) {
            if ($body['target_committees'] === null || $body['target_committees'] === '') {
                $data['target_committees'] = null;
            } elseif (!is_array($body['target_committees'])) {
                throw new HttpException('target_committees 需为ID数组', 400);
            } else {
                $ids = [];
                foreach ($body['target_committees'] as $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (!is_int($value) && !ctype_digit((string) $value)) {
                        throw new HttpException('target_committees 中仅允许数字ID', 400);
                    }
                    $ids[] = (int) $value;
                }
                $data['target_committees'] = empty($ids) ? null : array_values(array_unique($ids));
            }
        }

        if ($isCreate && (!isset($data['title']) || !isset($data['content']))) {
            throw new HttpException('缺少必要参数', 400);
        }

        return $data;
    }

    private function validateResponsePayload(array $body): array
    {
        if (!isset($body['summary']) || !is_string($body['summary']) || trim($body['summary']) === '') {
            throw new HttpException('请填写反馈', 400);
        }
        $maxLength = 2000;
        if (mb_strlen($body['summary']) > $maxLength) {
            throw new HttpException('反馈长度需小于2000字符', 400);
        }
        if (array_key_exists('file_path', $body) && $body['file_path'] !== null) {
            if (!is_string($body['file_path']) || mb_strlen($body['file_path']) > 500) {
                throw new HttpException('附件路径需小于500字符', 400);
            }
        }

        $summary = trim($body['summary']);
        $filePath = $body['file_path'] ?? null;

        return [
            'summary' => $summary,
            'actions' => null,
            'resources' => null,
            'file_path' => ($filePath === '' ? null : $filePath),
        ];
    }

    private function userHasPresidiumPrivileges(User $user): bool
    {
        if (in_array($user->role, ['admin', 'dais'], true)) {
            return true;
        }
        $permissions = $user->effectivePermissions();
        return in_array('presidium:manage', $permissions, true) || in_array('crisis:dispatch', $permissions, true);
    }

    private function getUserCommitteeId(User $user): ?int
    {
        $delegate = Delegate::query()->where('user_id', $user->id)->first();
        return $delegate ? (int) $delegate->committee_id : null;
    }

    private function crisisAcceptsCommittee(Crisis $crisis, ?int $committeeId): bool
    {
        $targets = $crisis->target_committees;
        if (empty($targets) || $targets === null) {
            return true;
        }
        if ($committeeId === null) {
            return false;
        }
        return in_array($committeeId, array_map('intval', $targets), true);
    }
}
