<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Message extends Model
{
    public $timestamps = false;

    protected $table = 'Messages';

    protected $fillable = [
        'time',
        'from_user_id',
        'target_id',
        'channel',
        'target',
        'committee_id',
        'content',
    ];

    protected $casts = [
        'time' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(?array $targetMeta = null): array
    {
        $targetMeta = $targetMeta ?? [];
        $timestamp = $this->time ? Carbon::parse($this->time) : Carbon::parse($this->created_at ?? Carbon::now('UTC'));

        return [
            'id' => $this->id,
            'target' => $this->target,
            'targetId' => $this->target_id,
            'channel' => $this->channel,
            'committee' => $this->committee?->only(['id', 'name', 'code']),
            'content' => $this->content,
            'time' => $timestamp->toIso8601String(),
            'sender' => $this->sender ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'role' => $this->sender->role,
            ] : null,
            'targetMeta' => $targetMeta,
        ];
    }
}
