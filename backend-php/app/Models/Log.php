<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'Logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'action',
        'target_table',
        'target_id',
        'meta_json',
        'timestamp',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'timestamp' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $actor = $this->actor;
        return [
            'id' => $this->id,
            'action' => $this->action,
            'targetTable' => $this->target_table,
            'targetId' => $this->target_id,
            'meta' => $this->meta_json ?? null,
            'timestamp' => $this->timestamp ? $this->timestamp->toIso8601String() : null,
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
                'role' => $actor->role,
                'email' => $actor->email,
            ] : null,
        ];
    }
}
