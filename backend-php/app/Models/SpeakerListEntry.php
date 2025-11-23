<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SpeakerListEntry extends Model
{
    protected $table = 'SpeakerListEntries';

    protected $fillable = [
        'speaker_list_id',
        'delegate_id',
        'position',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function speakerList(): BelongsTo
    {
        return $this->belongsTo(SpeakerList::class, 'speaker_list_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Delegate::class, 'delegate_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null;

        return [
            'id' => $this->id,
            'speakerListId' => $this->speaker_list_id,
            'delegateId' => $this->delegate_id,
            'country' => $this->delegate?->country,
            'delegateName' => $this->delegate?->user?->name,
            'position' => $this->position,
            'status' => $this->status,
            'createdAt' => $formatDate($this->created_at),
            'updatedAt' => $formatDate($this->updated_at),
        ];
    }
}
