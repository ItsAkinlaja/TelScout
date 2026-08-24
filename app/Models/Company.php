<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'domain', 'normalized_domain', 'website', 'careers_url',
        'linkedin_url', 'logo_url', 'description', 'industry', 'location',
        'size', 'tech_stack', 'contact_status', 'contact_email',
        'is_excluded', 'meta',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'meta' => 'array',
        'is_excluded' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }

    public function jobSources(): HasMany
    {
        return $this->hasMany(JobSource::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CompanySource::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Normalize a domain for deduplication.
     */
    public static function normalizeDomain(string $url): string
    {
        $host = parse_url(strtolower(trim($url)), PHP_URL_HOST) ?? $url;
        // strip www.
        $host = preg_replace('/^www\./', '', $host);
        // strip trailing slash / path
        return rtrim($host, '/');
    }

    /**
     * Find or create a company by domain, preventing duplicates.
     */
    public static function findOrCreateByDomain(array $data): static
    {
        $website = $data['website'] ?? null;
        $normalizedDomain = $website ? static::normalizeDomain($website) : null;

        if ($normalizedDomain) {
            $company = static::where('normalized_domain', $normalizedDomain)->first();
            if ($company) {
                $company->fill(array_filter($data, fn($v) => $v !== null));
                $company->save();
                return $company;
            }
        }

        // Attempt to match by name as fallback
        $company = static::where('name', $data['name'] ?? '')->first();
        if ($company) {
            return $company;
        }

        return static::create(array_merge($data, [
            'normalized_domain' => $normalizedDomain,
            'domain' => $normalizedDomain,
        ]));
    }
}
