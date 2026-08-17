<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSkill extends Model
{
    protected $fillable = ['job_listing_id', 'skill', 'is_required'];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
}
