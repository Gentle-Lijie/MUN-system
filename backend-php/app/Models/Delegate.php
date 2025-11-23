<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Delegate extends Model
{
    protected $table = 'Delegates';

    protected $fillable = [
        'user_id',
        'committee_id',
        'country',
        'veto_allowed',
    ];

    protected $casts = [
        'veto_allowed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null;

        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'committeeId' => $this->committee_id,
            'country' => $this->country,
            'vetoAllowed' => (bool) $this->veto_allowed,
            'userName' => $this->user?->name,
            'userEmail' => $this->user?->email,
            'userOrganization' => $this->user?->organization,
            'committeeName' => $this->committee?->name,
            'committeeCode' => $this->committee?->code,
            'createdAt' => $formatDate($this->created_at),
            'updatedAt' => $formatDate($this->updated_at),
        ];
    }
}
