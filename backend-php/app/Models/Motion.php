<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Motion extends Model
{
    protected $table = 'Motions';

    protected $fillable = [
        'committee_session_id',
        'motion_type',
        'proposer_id',
        'file_id',
        'unit_time_seconds',
        'total_time_seconds',
        'speaker_list_id',
        'vote_required',
        'veto_applicable',
        'state',
        'vote_result',
    ];

    protected $attributes = [
        'proposer_id' => null,
    ];

    protected $casts = [
        'vote_required' => 'boolean',
        'veto_applicable' => 'boolean',
        'vote_result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function committeeSession(): BelongsTo
    {
        return $this->belongsTo(CommitteeSession::class, 'committee_session_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(Delegate::class, 'proposer_id');
    }

    public function speakerListRelation(): BelongsTo
    {
        return $this->belongsTo(SpeakerList::class, 'speaker_list_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null;

        return [
            'id' => $this->id,
            'committeeSessionId' => $this->committee_session_id,
            'motionType' => $this->motion_type,
            'proposerId' => $this->proposer_id,
            'fileId' => $this->file_id,
            'unitTimeSeconds' => $this->unit_time_seconds,
            'totalTimeSeconds' => $this->total_time_seconds,
            'speakerListId' => $this->speaker_list_id,
            'voteRequired' => (bool) $this->vote_required,
            'vetoApplicable' => (bool) $this->veto_applicable,
            'state' => $this->state,
            'voteResult' => $this->vote_result,
            'createdAt' => $formatDate($this->created_at),
            'updatedAt' => $formatDate($this->updated_at),
        ];
    }
}
