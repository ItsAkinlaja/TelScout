<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Opportunity extends Model
{
    protected $fillable = [
        'user_id', 'job_listing_id', 'company_id', 'contact_id',
        'match_score', 'match_classification', 'matched_skills',
        'missing_skills', 'match_reasoning', 'cv_tailoring_suggestions',
        'score_breakdown', 'status', 'application_url', 'notes',
        'discovered_at', 'applied_at', 'interview_dates',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'matched_skills' => 'array',
        'missing_skills' => 'array',
        'score_breakdown' => 'array',
        'interview_dates' => 'array',
        'discovered_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }
}
