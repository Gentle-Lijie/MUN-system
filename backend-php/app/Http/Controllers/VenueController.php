<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Committee;
use App\Models\CommitteeSession;
use App\Support\Auth as AuthSupport;
use App\Support\Validation;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $committees = Committee::query()->with('sessions')->orderBy('code')->get();
        return $this->json([
            'items' => $committees->map(static fn (Committee $committee) => $committee->toApiResponse())->all(),
            'total' => $committees->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = AuthSupport::requirePresidium($this->app, $request);
        $payload = $this->body($request);
        if (!$payload) {
            throw new HttpException('Request body is required', 400);
        }

        $code = strtoupper(trim((string) ($payload['code'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        if ($code === '' || $name === '') {
            throw new HttpException('code and name are required', 400);
        }
        $exists = Committee::query()->whereRaw('UPPER(code) = ?', [$code])->exists();
        if ($exists) {
            throw new HttpException('Venue code already exists', 409);
        }

        $committee = new Committee();
        $committee->code = $code;
        $committee->name = $name;
        $committee->created_by = $user->id;

        $venue = $payload['venue'] ?? ($payload['location'] ?? null);
        $committee->venue = $venue ? trim((string) $venue) : null;
        if (array_key_exists('description', $payload)) {
            $description = $payload['description'];
            $committee->description = $description ? trim((string) $description) : null;
        }

        $status = strtolower(trim((string) ($payload['status'] ?? 'preparation')));
        $allowedStatus = ['preparation', 'in_session', 'paused', 'closed'];
        if (!in_array($status, $allowedStatus, true)) {
            throw new HttpException('Unsupported status value', 400);
        }
        $committee->status = $status;

        if (array_key_exists('capacity', $payload)) {
            $capacity = Validation::int($payload['capacity'], 'capacity');
            if ($capacity <= 0) {
                throw new HttpException('capacity must be greater than zero', 400);
            }
            $committee->capacity = $capacity;
        }

        if (array_key_exists('dais', $payload)) {
            $dais = $payload['dais'];
            if ($dais === null) {
                $committee->setDaisMembers([]);
            } elseif (!is_array($dais)) {
                throw new HttpException('dais must be a list when provided', 400);
            } else {
                foreach ($dais as $item) {
                    if (!is_array($item) || !isset($item['id'])) {
                        throw new HttpException('each dais item must have id and role', 400);
                    }
                }
                $committee->setDaisMembers($dais);
            }
        }

        if (array_key_exists('timeConfig', $payload)) {
            $config = $payload['timeConfig'];
            if ($config === null) {
                $committee->setTimeConfig(null);
            } elseif (!is_array($config)) {
                throw new HttpException('timeConfig must be an object', 400);
            } else {
                $committee->setTimeConfig([
                    'realTimeAnchor' => $config['realTimeAnchor'] ?? null,
                    'flowSpeed' => $config['flowSpeed'] ?? 1,
                ]);
            }
        }

        $committee->save();
        $committee->load('sessions');

        return $this->json($committee->toApiResponse(), 201);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $committee = $this->findCommittee((int) $params['venueId']);
        $payload = $this->body($request);
        if (!$payload) {
            throw new HttpException('Request body is required', 400);
        }

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                throw new HttpException('name cannot be empty', 400);
            }
            $committee->name = $name;
        }
        if (array_key_exists('code', $payload)) {
            $code = strtoupper(trim((string) $payload['code']));
            if ($code === '') {
                throw new HttpException('code cannot be empty', 400);
            }
            $committee->code = $code;
        }
        if (array_key_exists('venue', $payload) || array_key_exists('location', $payload)) {
            $value = $payload['venue'] ?? $payload['location'];
            $committee->venue = $value ? trim((string) $value) : null;
        }
        if (array_key_exists('description', $payload)) {
            $value = $payload['description'];
            $committee->description = $value ? trim((string) $value) : null;
        }
        if (array_key_exists('status', $payload)) {
            $status = strtolower(trim((string) $payload['status']));
            $allowed = ['preparation', 'in_session', 'paused', 'closed'];
            if (!in_array($status, $allowed, true)) {
                throw new HttpException('Unsupported status value', 400);
            }
            $committee->status = $status;
        }
        if (array_key_exists('capacity', $payload)) {
            $capacity = Validation::int($payload['capacity'], 'capacity');
            if ($capacity <= 0) {
                throw new HttpException('capacity must be greater than zero', 400);
            }
            $committee->capacity = $capacity;
        }
        if (array_key_exists('dais', $payload)) {
            $dais = $payload['dais'];
            if ($dais === null) {
                $committee->setDaisMembers([]);
            } elseif (!is_array($dais)) {
                throw new HttpException('dais must be a list when provided', 400);
            } else {
                foreach ($dais as $item) {
                    if (!is_array($item) || !isset($item['id'])) {
                        throw new HttpException('each dais item must have id and role', 400);
                    }
                }
                $committee->setDaisMembers($dais);
            }
        }
        if (array_key_exists('timeConfig', $payload)) {
            $config = $payload['timeConfig'];
            if ($config === null) {
                $committee->setTimeConfig(null);
            } elseif (!is_array($config)) {
                throw new HttpException('timeConfig must be an object', 400);
            } else {
                $committee->setTimeConfig([
                    'realTimeAnchor' => $config['realTimeAnchor'] ?? null,
                    'flowSpeed' => $config['flowSpeed'] ?? 1,
                ]);
            }
        }

        $committee->save();
        $committee->load('sessions');
        return $this->json($committee->toApiResponse());
    }

    public function addSession(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);
        $committee = $this->findCommittee((int) $params['venueId']);
        $payload = $this->body($request);

        $topic = trim((string) ($payload['topic'] ?? ($payload['name'] ?? '')));
        if ($topic === '') {
            throw new HttpException('topic is required', 400);
        }
        $startRaw = $payload['start'] ?? ($payload['time'] ?? null);
        $start = $this->parseIsoDatetime($startRaw);
        $durationRaw = $payload['durationMinutes'] ?? ($payload['duration'] ?? 30);
        $duration = Validation::int($durationRaw, 'durationMinutes');
        if ($duration <= 0) {
            throw new HttpException('durationMinutes must be positive', 400);
        }
        $chair = $payload['chair'] ?? null;

        $session = new CommitteeSession();
        $session->topic = $topic;
        $session->chair = $chair ? trim((string) $chair) : null;
        $session->start_time = $start;
        $session->duration_minutes = $duration;
        $session->committee()->associate($committee);
        $session->save();

        return $this->json($session->toApiResponse(), 201);
    }

    private function parseIsoDatetime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        if (!is_string($value)) {
            throw new HttpException('start must be an ISO timestamp string', 400);
        }
        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }
        if (str_ends_with($normalized, 'Z')) {
            $normalized = substr($normalized, 0, -1) . '+00:00';
        }
        try {
            return new DateTimeImmutable($normalized);
        } catch (\Exception) {
            throw new HttpException('Invalid datetime format, expected ISO8601 string', 400);
        }
    }

    private function findCommittee(int $id): Committee
    {
        $committee = Committee::query()->with('sessions')->find($id);
        if (!$committee) {
            throw new HttpException(sprintf('Venue %d not found', $id), 404);
        }
        return $committee;
    }
}
