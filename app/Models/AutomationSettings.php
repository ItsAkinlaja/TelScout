<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AutomationSettings extends Model
{
    protected $fillable = [
        'user_id', 'daily_send_limit', 'hourly_send_limit', 'auto_send',
        'require_approval', 'working_hours_start', 'working_hours_end',
        'timezone', 'min_delay_seconds', 'max_delay_seconds',
        'follow_up_interval_days', 'max_follow_ups', 'minimum_match_score',
        'discovery_enabled', 'search_keywords', 'search_locations',
        'remote_only', 'minimum_salary',
        // OAuth (stored encrypted, set via setters)
        'google_client_id_encrypted', 'google_client_secret_encrypted', 'google_redirect_uri',
        // AI
        'ai_provider', 'ai_api_key_encrypted', 'ai_model', 'ai_temperature', 'ai_max_tokens',
        // Contact enrichment (stored encrypted, set via setters)
        'hunter_api_key_encrypted', 'apollo_api_key_encrypted',
    ];

    protected $casts = [
        'auto_send'         => 'boolean',
        'require_approval'  => 'boolean',
        'discovery_enabled' => 'boolean',
        'remote_only'       => 'boolean',
        'search_keywords'   => 'array',
        'search_locations'  => 'array',
        'minimum_salary'    => 'decimal:2',
        'ai_temperature'    => 'float',
        'ai_max_tokens'     => 'integer',
    ];

    protected $hidden = [
        'google_client_id_encrypted',
        'google_client_secret_encrypted',
        'ai_api_key_encrypted',
        'hunter_api_key_encrypted',
        'apollo_api_key_encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Google OAuth ──────────────────────────────────────────────────────────

    public function setGoogleClientId(string $value): void
    {
        $this->google_client_id_encrypted = Crypt::encryptString($value);
    }

    public function getGoogleClientId(): ?string
    {
        return $this->google_client_id_encrypted
            ? Crypt::decryptString($this->google_client_id_encrypted)
            : null;
    }

    public function setGoogleClientSecret(string $value): void
    {
        $this->google_client_secret_encrypted = Crypt::encryptString($value);
    }

    public function getGoogleClientSecret(): ?string
    {
        return $this->google_client_secret_encrypted
            ? Crypt::decryptString($this->google_client_secret_encrypted)
            : null;
    }

    public function getEffectiveRedirectUri(): string
    {
        return $this->google_redirect_uri
            ?? config('services.google.redirect', url('/api/gmail/callback'));
    }

    // ── AI ────────────────────────────────────────────────────────────────────

    public function setAiApiKey(string $value): void
    {
        $this->ai_api_key_encrypted = Crypt::encryptString($value);
    }

    public function getAiApiKey(): ?string
    {
        return $this->ai_api_key_encrypted
            ? Crypt::decryptString($this->ai_api_key_encrypted)
            : null;
    }

    // ── Contact enrichment ────────────────────────────────────────────────────

    public function setHunterApiKey(string $value): void
    {
        $this->hunter_api_key_encrypted = Crypt::encryptString($value);
    }

    public function getHunterApiKey(): ?string
    {
        return $this->hunter_api_key_encrypted
            ? Crypt::decryptString($this->hunter_api_key_encrypted)
            : null;
    }

    public function setApolloApiKey(string $value): void
    {
        $this->apollo_api_key_encrypted = Crypt::encryptString($value);
    }

    public function getApolloApiKey(): ?string
    {
        return $this->apollo_api_key_encrypted
            ? Crypt::decryptString($this->apollo_api_key_encrypted)
            : null;
    }

    // ── Presence checks ───────────────────────────────────────────────────────

    public function hasGoogleOAuth(): bool
    {
        return !empty($this->google_client_id_encrypted);
    }

    public function hasAiKey(): bool
    {
        return !empty($this->ai_api_key_encrypted);
    }

    public function hasHunterKey(): bool
    {
        return !empty($this->hunter_api_key_encrypted);
    }

    public function hasApolloKey(): bool
    {
        return !empty($this->apollo_api_key_encrypted);
    }
}
