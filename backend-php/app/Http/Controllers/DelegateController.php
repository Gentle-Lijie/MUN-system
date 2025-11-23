<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Committee;
use App\Models\Delegate;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use App\Support\Validation;
use Illuminate\Database\QueryException;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DelegateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $query = Delegate::query()->with(['user', 'committee']);
        $args = $request->query;

        if ($committeeId = $args->get('committeeId')) {
            $query->where('committee_id', Validation::int($committeeId, 'committeeId'));
        }
        if ($userId = $args->get('userId')) {
            $query->where('user_id', Validation::int($userId, 'userId'));
        }
        if ($committeeCode = $args->get('committeeCode')) {
            $code = strtoupper(trim((string) $committeeCode));
            $query->whereHas('committee', static function ($builder) use ($code): void {
                $builder->whereRaw('UPPER(code) = ?', [$code]);
            });
        }
        if ($search = $args->get('search')) {
            $needle = '%' . strtolower(trim((string) $search)) . '%';
            $query->where(static function ($builder) use ($needle): void {
                $builder->whereRaw('LOWER(country) LIKE ?', [$needle])
                    ->orWhereHas('user', static function ($userQuery) use ($needle): void {
                        $userQuery->whereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$needle]);
                    })
                    ->orWhereHas('committee', static function ($committeeQuery) use ($needle): void {
                        $committeeQuery->whereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(code) LIKE ?', [$needle]);
                    });
            });
        }

        $page = max(1, (int) ($args->get('page', 1)));
        $pageSize = (int) ($args->get('pageSize', 200));
        $pageSize = max(1, min($pageSize, 500));

        $total = (clone $query)->count();
        $delegates = $query
            ->orderByDesc('updated_at')
            ->forPage($page, $pageSize)
            ->get();

        return $this->json([
            'items' => $delegates->map(static fn (Delegate $delegate) => $delegate->toApiResponse())->all(),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $payload = $this->body($request);
        $delegateId = isset($payload['id']) ? Validation::int($payload['id'], 'id') : null;

        if ($delegateId) {
            $delegate = $this->findDelegate($delegateId);
        } else {
            foreach (['userId', 'committeeId', 'country'] as $field) {
                if (empty($payload[$field])) {
                    throw new HttpException(sprintf('Missing required field: %s', $field), 400);
                }
            }
            $delegate = new Delegate();
        }

        if (isset($payload['userId'])) {
            $user = $this->ensureDelegateUser(Validation::int($payload['userId'], 'userId'));
            $delegate->user()->associate($user);
        }
        if (isset($payload['committeeId'])) {
            $committee = $this->ensureCommittee(Validation::int($payload['committeeId'], 'committeeId'));
            $delegate->committee()->associate($committee);
        }
        if (isset($payload['country'])) {
            $country = trim((string) $payload['country']);
            if ($country === '') {
                throw new HttpException('country cannot be empty', 400);
            }
            $delegate->country = $country;
        }
        if (array_key_exists('vetoAllowed', $payload)) {
            $delegate->veto_allowed = Validation::bool($payload['vetoAllowed']);
        }

        try {
            $delegate->save();
        } catch (QueryException $exception) {
            throw new HttpException('该代表已被分配至该会场或数据无效', 400, ['error' => $exception->getMessage()]);
        }

        $delegate->load(['user', 'committee']);
        return $this->json($delegate->toApiResponse(), $delegateId ? 200 : 201);
    }

    public function import(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $file = $request->files->get('file');
        if ($file === null || !$file->isValid()) {
            throw new HttpException('请上传 CSV 文件', 400);
        }
        $content = file_get_contents($file->getRealPath());
        if ($content === false || $content === '') {
            throw new HttpException('Uploaded file is empty', 400);
        }

        $content = $this->ensureUtf8($content);
        $reader = Reader::createFromString($content);
        $reader->setHeaderOffset(0);
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($reader->getRecords() as $index => $row) {
            $line = $index + 2;
            try {
                $userEmail = strtolower(trim((string) ($row['userEmail'] ?? '')));
                $committeeCode = strtoupper(trim((string) ($row['committeeCode'] ?? '')));
                $country = trim((string) ($row['country'] ?? ''));
                $vetoAllowed = Validation::bool($row['vetoAllowed'] ?? null);

                if ($userEmail === '' || $committeeCode === '' || $country === '') {
                    throw new HttpException('userEmail、committeeCode 和 country 为必填列', 400);
                }

                $user = User::query()->whereRaw('LOWER(email) = ?', [$userEmail])->first();
                if (!$user) {
                    throw new HttpException(sprintf('找不到邮箱为 %s 的用户', $userEmail), 400);
                }
                if ($user->role !== 'delegate') {
                    throw new HttpException(sprintf('用户 %s 不是 delegate 角色', $userEmail), 400);
                }

                $committee = Committee::query()->whereRaw('UPPER(code) = ?', [$committeeCode])->first();
                if (!$committee) {
                    throw new HttpException(sprintf('找不到会场代码 %s', $committeeCode), 400);
                }

                $delegate = Delegate::query()->where('user_id', $user->id)->where('committee_id', $committee->id)->first();
                if ($delegate) {
                    $delegate->country = $country;
                    $delegate->veto_allowed = $vetoAllowed;
                    $delegate->save();
                    $updated++;
                } else {
                    $delegate = new Delegate();
                    $delegate->user()->associate($user);
                    $delegate->committee()->associate($committee);
                    $delegate->country = $country;
                    $delegate->veto_allowed = $vetoAllowed;
                    $delegate->save();
                    $created++;
                }
            } catch (HttpException $exception) {
                $errors[] = sprintf('第 %d 行：%s', $line, $exception->getMessage());
            }
        }

        return $this->json([
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    public function export(Request $request): Response
    {
        AuthSupport::requirePresidium($this->app, $request);
        $delegates = Delegate::query()->with(['user', 'committee'])->orderBy('committee_id')->orderBy('country')->get();
        $writer = Writer::createFromString('');
        $writer->insertOne(['userEmail', 'userName', 'committeeCode', 'committeeName', 'country', 'vetoAllowed']);
        foreach ($delegates as $delegate) {
            $writer->insertOne([
                $delegate->user?->email,
                $delegate->user?->name,
                $delegate->committee?->code,
                $delegate->committee?->name,
                $delegate->country,
                $delegate->veto_allowed ? '1' : '0',
            ]);
        }
        $csv = (string) $writer->toString();
        $body = @iconv('UTF-8', 'GBK//IGNORE', $csv) ?: $csv;
        $response = new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=gbk',
            'Content-Disposition' => 'attachment; filename=delegates.csv',
        ]);
        return $response;
    }

    public function byCommittee(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $committee = $this->ensureCommittee((int) $params['committeeId']);
        $delegates = Delegate::query()
            ->with(['user'])
            ->where('committee_id', $committee->id)
            ->orderBy('country')
            ->get();

        return $this->json([
            'items' => $delegates->map(static fn (Delegate $delegate) => $delegate->toApiResponse())->all(),
            'total' => $delegates->count(),
            'committee' => [
                'id' => $committee->id,
                'name' => $committee->name,
                'code' => $committee->code,
            ],
        ]);
    }

    private function ensureDelegateUser(int $userId): User
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw new HttpException('指定的用户不存在', 400);
        }
        if ($user->role !== 'delegate') {
            throw new HttpException('仅允许将代表赋予角色为 delegate 的用户', 400);
        }
        return $user;
    }

    private function ensureCommittee(int $committeeId): Committee
    {
        $committee = Committee::query()->find($committeeId);
        if (!$committee) {
            throw new HttpException('指定的会场不存在', 400);
        }
        return $committee;
    }

    private function findDelegate(int $id): Delegate
    {
        $delegate = Delegate::query()->with(['user', 'committee'])->find($id);
        if (!$delegate) {
            throw new HttpException(sprintf('Delegate %d not found', $id), 404);
        }
        return $delegate;
    }

    private function ensureUtf8(string $data): string
    {
        if (function_exists('mb_detect_encoding') && mb_detect_encoding($data, 'UTF-8', true)) {
            return $data;
        }
        $converted = @iconv('GBK', 'UTF-8//IGNORE', $data);
        return $converted !== false ? $converted : $data;
    }
}
