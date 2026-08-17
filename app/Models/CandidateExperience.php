<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExperience extends Model
{
    protected $fillable = [
        'candidate_profile_id', 'company', 'title', 'description',
        'start_date', 'end_date', 'is_current', 'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class, 'candidate_profile_id');
    }
}
