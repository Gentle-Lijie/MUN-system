<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Committee extends Model
{
    protected $table = 'Committees';

    protected $fillable = [
        'code',
        'name',
        'venue',
        'description',
        'status',
        'capacity',
        'agenda_order',
        'dais_json',
        'time_config',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(CommitteeSession::class, 'committee_id')->orderBy('start_time');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function daisMembers(): array
    {
        $raw = $this->dais_json;
        if (!$raw) {
            return [];
        }
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach ($decoded as $item) {
            if (is_array($item) && isset($item['id'])) {
                $ids[] = (int) $item['id'];
            }
        }
        $users = [];
        if ($ids) {
            $users = User::query()->whereIn('id', $ids)->get()->keyBy('id')->all();
        }
        $result = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['id'])) {
                continue;
            }
            $id = (int) $item['id'];
            $user = $users[$id] ?? null;
            $result[] = [
                'id' => $id,
                'name' => $user?->name ?? 'Unknown',
                'role' => $item['role'] ?? '主席团',
                'contact' => $user?->phone,
            ];
        }
        return $result;
    }

    /**
     * @param array<int, array{id:int|string, role?:string}> $value
     */
    public function setDaisMembers(array $value): void
    {
        $normalized = array_map(static function ($item) {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'role' => (string) ($item['role'] ?? '主席团'),
            ];
        }, $value);
        $this->dais_json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function timeConfig(): array
    {
        $raw = $this->time_config;
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return ['realTimeAnchor' => null, 'flowSpeed' => 1];
    }

    public function setTimeConfig(?array $config): void
    {
        $this->time_config = $config ? json_encode($config, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * @param Collection<int, CommitteeSession>|null $sessions
     * @return array<string, mixed>
     */
    public function toApiResponse(?Collection $sessions = null): array
    {
        $sessions = $sessions ?? $this->sessions;
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'venue' => $this->venue,
            'status' => $this->status,
            'capacity' => (int) ($this->capacity ?? 0),
            'description' => $this->description,
            'dais' => $this->daisMembers(),
            'sessions' => $sessions->map(static fn (CommitteeSession $session) => $session->toApiResponse())->all(),
            'timeConfig' => $this->timeConfig(),
            'createdAt' => $formatDate($this->created_at),
            'updatedAt' => $formatDate($this->updated_at),
        ];
    }
}
