<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    protected $fillable = [
        'company_id', 'title', 'description', 'location', 'is_remote',
        'employment_type', 'salary_min', 'salary_max', 'salary_currency',
        'application_url', 'source_url', 'external_id', 'source',
        'status', 'posted_at', 'expires_at', 'meta',
        'country', 'city', 'workplace_type', 'experience_level',
        'content_hash', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'is_remote'     => 'boolean',
        'salary_min'    => 'decimal:2',
        'salary_max'    => 'decimal:2',
        'posted_at'     => 'datetime',
        'expires_at'    => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'meta'          => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(JobSkill::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function getSkillNamesAttribute(): array
    {
        return $this->skills->pluck('skill')->map(fn($s) => strtolower($s))->toArray();
    }

    /**
     * Generate a SHA-256 content hash for deduplication across sources.
     */
    public static function generateContentHash(string $company, string $title, string $location): string
    {
        return hash(
            'sha256',
            strtolower(trim($company)) . '|' . strtolower(trim($title)) . '|' . strtolower(trim($location))
        );
    }
}
