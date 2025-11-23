<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrisisResponse extends Model
{
    protected $table = 'CrisisResponses';

    protected $fillable = [
        'crisis_id',
        'user_id',
        'content',
        'file_path',
    ];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function crisis(): BelongsTo
    {
        return $this->belongsTo(Crisis::class, 'crisis_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
