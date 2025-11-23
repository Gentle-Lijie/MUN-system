<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crisis extends Model
{
    protected $table = 'Crises';

    protected $fillable = [
        'title',
        'content',
        'file_path',
        'published_by',
        'published_at',
        'target_committees',
        'status',
        'responses_allowed',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'target_committees' => 'array',
        'responses_allowed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CrisisResponse::class, 'crisis_id');
    }
}
