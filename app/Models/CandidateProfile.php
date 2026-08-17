<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateProfile extends Model
{
    protected $fillable = [
        'user_id', 'full_name', 'primary_title', 'location', 'portfolio_url',
        'summary', 'cv_path', 'preferred_roles', 'preferred_locations',
        'work_preference', 'minimum_salary', 'preferred_currencies',
        'preferred_industries', 'excluded_industries', 'preferred_technologies',
        'years_of_experience',
    ];

    protected $casts = [
        'preferred_roles' => 'array',
        'preferred_locations' => 'array',
        'preferred_currencies' => 'array',
        'preferred_industries' => 'array',
        'excluded_industries' => 'array',
        'preferred_technologies' => 'array',
        'minimum_salary' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class)->orderBy('sort_order');
    }

    public function getSkillNamesAttribute(): array
    {
        return $this->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
    }
}
