<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeakerList extends Model
{
    protected $table = 'SpeakerLists';

    protected $fillable = [
        'committee_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SpeakerListEntry::class, 'speaker_list_id')->orderBy('position');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'committeeId' => $this->committee_id,
            'entries' => $this->entries->map(fn ($entry) => $entry->toApiResponse())->toArray(),
        ];
    }
}
