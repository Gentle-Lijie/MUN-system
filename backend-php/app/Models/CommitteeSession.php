<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CommitteeSession extends Model
{
    protected $table = 'CommitteeSessions';

    protected $fillable = [
        'committee_id',
        'topic',
        'chair',
        'start_time',
        'duration_minutes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'chair' => $this->chair,
            'start' => $this->start_time ? Carbon::parse($this->start_time)->toIso8601String() : null,
            'durationMinutes' => (int) $this->duration_minutes,
        ];
    }
}
