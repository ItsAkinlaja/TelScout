<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSkill extends Model
{
    protected $fillable = ['candidate_profile_id', 'skill', 'level', 'years'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class, 'candidate_profile_id');
    }
}
