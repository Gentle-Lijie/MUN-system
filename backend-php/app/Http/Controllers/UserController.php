<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use DateTimeImmutable;
use DateTimeZone;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);

        $query = User::query();
        $role = $this->normalizeRole($request->query->get('role'));
        $committee = trim((string) $request->query->get('committee', ''));
        $search = trim((string) $request->query->get('search', ''));

        if ($role) {
            $query->where('role', $role);
        }
        if ($committee !== '') {
            $needle = '%' . strtolower($committee) . '%';
            $query->whereRaw('LOWER(organization) LIKE ?', [$needle]);
        }
        if ($search !== '') {
            $needle = '%' . strtolower($search) . '%';
            $query->where(static function ($builder) use ($needle): void {
                $builder
                    ->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(organization) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(phone) LIKE ?', [$needle]);
            });
        }

        $users = $query->orderByDesc('created_at')->get();
        return $this->json([
            'items' => $users->map(static fn (User $user) => $user->toApiResponse())->all(),
            'total' => $users->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $payload = $this->body($request);
        $name = trim((string) ($payload['name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $role = $this->normalizeRole($payload['role'] ?? null);
        $organization = $this->extractOrganization($payload);
        $phone = $this->sanitizeString($payload['phone'] ?? null);

        if ($name === '' || $email === '' || !$role) {
            throw new HttpException('name, email and role are required', 400);
        }
        if ($this->emailExists($email)) {
            throw new HttpException('Email already exists', 409);
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->role = $role;
        $user->organization = $organization;
        $user->phone = $phone;
        $user->setPassword(User::DEFAULT_PASSWORD);
        $user->setEffectivePermissions(User::defaultPermissions($role));
        $user->save();

        return $this->json($user->toApiResponse(), 201);
    }

    public function show(Request $request, array $params): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $user = $this->findUser((int) $params['userId']);
        return $this->json($user->toApiResponse());
    }

    public function update(Request $request, array $params): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $user = $this->findUser((int) $params['userId']);
        $payload = $this->body($request);

        if (isset($payload['name'])) {
            $user->name = $this->sanitizeString($payload['name']);
        }
        if (isset($payload['email'])) {
            $newEmail = strtolower($this->sanitizeString($payload['email']));
            if ($newEmail === '') {
                throw new HttpException('Email cannot be blank', 400);
            }
            if ($newEmail !== $user->email && $this->emailExists($newEmail, $user->id)) {
                throw new HttpException('Email already exists', 409);
            }
            $user->email = $newEmail;
        }
        if (isset($payload['role'])) {
            $role = $this->normalizeRole($payload['role']);
            if (!$role) {
                throw new HttpException('Unsupported role value', 400);
            }
            $user->role = $role;
        }
        if (array_key_exists('organization', $payload) || array_key_exists('committee', $payload)) {
            $user->organization = $this->extractOrganization($payload);
        }
        if (isset($payload['phone'])) {
            $user->phone = $this->sanitizeString($payload['phone']);
        }
        if (!empty($payload['resetPassword'])) {
            $user->setPassword(User::DEFAULT_PASSWORD);
        }

        $user->save();
        return $this->json($user->toApiResponse());
    }

    public function import(Request $request): JsonResponse
    {
        AuthSupport::requireAdmin($this->app, $request);
        $file = $request->files->get('file');
        if ($file === null || !$file->isValid()) {
            throw new HttpException('CSV file is required under "file" field', 400);
        }
        $content = file_get_contents($file->getRealPath());
        if ($content === false || $content === '') {
            throw new HttpException('Uploaded file is empty', 400);
        }

        $content = $this->ensureUtf8($content);
        $reader = Reader::createFromString($content);
        $reader->setHeaderOffset(0);
        $header = $reader->getHeader();
        $required = ['name', 'email', 'role', 'organization', 'phone'];
        $missing = array_diff($required, $header ?? []);
        if ($missing) {
            throw new HttpException('Missing columns in CSV: ' . implode(', ', $missing), 400);
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($reader->getRecords() as $index => $row) {
            $line = $index + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $role = $this->normalizeRole($row['role'] ?? null);
            $organization = $this->sanitizeString($row['organization'] ?? ($row['committee'] ?? null));
            $phone = $this->sanitizeString($row['phone'] ?? null);

            if ($name === '' || $email === '' || !$role) {
                $errors[] = sprintf('Row %d: missing required field', $line);
                continue;
            }

            $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($existing) {
                $existing->name = $name;
                $existing->role = $role;
                $existing->organization = $organization;
                $existing->phone = $phone;
                $existing->save();
                $updated++;
            } else {
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->role = $role;
                $user->organization = $organization;
                $user->phone = $phone;
                $user->setPassword(User::DEFAULT_PASSWORD);
                $user->setEffectivePermissions(User::defaultPermissions($role));
                $user->save();
                $created++;
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
        AuthSupport::requireAdmin($this->app, $request);
        $users = User::query()->orderBy('id')->get();
        $writer = Writer::createFromString('');
        $writer->setDelimiter(',');
        $writer->insertOne(['name', 'email', 'role', 'organization', 'phone']);
        foreach ($users as $user) {
            $writer->insertOne([
                $user->name,
                $user->email,
                $user->role,
                $user->organization ?? '',
                $user->phone ?? '',
            ]);
        }
        $csv = (string) $writer->toString();
        $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Ymd_His');
        $filename = sprintf('users_%s.csv', $timestamp);
        $body = $this->convertEncoding($csv);

        $response = new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=gbk',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
        return $response;
    }

    private function normalizeRole(mixed $role): ?string
    {
        if (!is_string($role)) {
            return null;
        }
        $normalized = strtolower(trim($role));
        if ($normalized === 'chair') {
            $normalized = 'dais';
        }
        return in_array($normalized, User::ROLE_CHOICES, true) ? $normalized : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractOrganization(array $payload): ?string
    {
        $value = $payload['organization'] ?? $payload['committee'] ?? null;
        $value = $this->sanitizeString($value);
        return $value === '' ? null : $value;
    }

    private function sanitizeString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    private function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $query = User::query()->whereRaw('LOWER(email) = ?', [$email]);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    private function findUser(int $id): User
    {
        $user = User::query()->find($id);
        if (!$user) {
            throw new HttpException(sprintf('User %d not found', $id), 404);
        }
        return $user;
    }

    private function convertEncoding(string $data): string
    {
        $converted = @iconv('UTF-8', 'GBK//IGNORE', $data);
        return $converted !== false ? $converted : $data;
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
