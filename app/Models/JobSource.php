<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'source_type',
        'source_url',
        'ats_type',
        'active',
        'last_fetched_at',
        'next_fetch_at',
        'failure_count',
        'meta',
    ];

    protected $casts = [
        'active'          => 'boolean',
        'last_fetched_at' => 'datetime',
        'next_fetch_at'   => 'datetime',
        'meta'            => 'array',
        'failure_count'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Only active sources.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', true);
    }

    /**
     * Sources due for fetching: next_fetch_at <= now or next_fetch_at is null.
     */
    public function scopeDue(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('next_fetch_at')
              ->orWhere('next_fetch_at', '<=', now());
        });
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    /**
     * Record a fetch attempt result.
     *
     * On success: reset failure_count, update last_fetched_at, schedule next fetch.
     * On failure: increment failure_count, disable if >= 5.
     */
    public function markFetched(bool $success): void
    {
        $now = now();

        if ($success) {
            $this->update([
                'last_fetched_at' => $now,
                'failure_count'   => 0,
                'active'          => true,
                'next_fetch_at'   => $this->calculateNextFetchAt($now),
            ]);
        } else {
            $newFailureCount = $this->failure_count + 1;
            $this->update([
                'last_fetched_at' => $now,
                'failure_count'   => $newFailureCount,
                'active'          => $newFailureCount < 5,
                'next_fetch_at'   => $this->calculateNextFetchAt($now),
            ]);
        }
    }

    /**
     * Calculate the next scheduled fetch time based on source_type.
     * ATS sources (greenhouse, lever, ashby) → +6 hours.
     * Generic sources → +24 hours.
     */
    private function calculateNextFetchAt(Carbon $from): Carbon
    {
        $atsTypes = ['greenhouse', 'lever', 'ashby'];

        if (in_array($this->source_type, $atsTypes, true)) {
            return $from->copy()->addHours(6);
        }

        return $from->copy()->addHours(24);
    }
}
