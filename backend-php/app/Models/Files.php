<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Files extends Model
{
    protected $table = 'Files';

    protected $fillable = [
        'committee_id',
        'type',
        'title',
        'description',
        'content_path',
        'submitted_by',
        'submitted_at',
        'status',
        'visibility',
        'dias_fb',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}