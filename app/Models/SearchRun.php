<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchRun extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'criteria', 'status',
        'results_count', 'new_companies', 'new_jobs',
        'error_message', 'meta', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'criteria'     => 'array',
        'meta'         => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
