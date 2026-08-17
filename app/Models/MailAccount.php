<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class MailAccount extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'label', 'email', 'is_active', 'is_default',
        'access_token_encrypted', 'refresh_token_encrypted',
        'token_expires_at', 'scopes', 'connected_at',
        'oauth_client_id_encrypted', 'oauth_client_secret_encrypted', 'oauth_redirect_uri',
        'smtp_host', 'smtp_port', 'smtp_encryption',
        'smtp_username_encrypted', 'smtp_password_encrypted',
        'meta',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_default'       => 'boolean',
        'scopes'           => 'array',
        'meta'             => 'array',
        'token_expires_at' => 'datetime',
        'connected_at'     => 'datetime',
    ];

    protected $hidden = [
        'access_token_encrypted', 'refresh_token_encrypted',
        'oauth_client_id_encrypted', 'oauth_client_secret_encrypted',
        'smtp_username_encrypted', 'smtp_password_encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── OAuth tokens ─────────────────────────────────────────────────────────

    public function setAccessToken(string $token): void
    {
        $this->access_token_encrypted = Crypt::encryptString($token);
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token_encrypted
            ? Crypt::decryptString($this->access_token_encrypted)
            : null;
    }

    public function setRefreshToken(string $token): void
    {
        $this->refresh_token_encrypted = Crypt::encryptString($token);
    }

    public function getRefreshToken(): ?string
    {
        return $this->refresh_token_encrypted
            ? Crypt::decryptString($this->refresh_token_encrypted)
            : null;
    }

    public function isTokenExpired(): bool
    {
        return !$this->token_expires_at || $this->token_expires_at->isPast();
    }

    // ── OAuth app credentials ────────────────────────────────────────────────

    public function setOAuthClientId(string $value): void
    {
        $this->oauth_client_id_encrypted = Crypt::encryptString($value);
    }

    public function getOAuthClientId(): ?string
    {
        return $this->oauth_client_id_encrypted
            ? Crypt::decryptString($this->oauth_client_id_encrypted)
            : null;
    }

    public function setOAuthClientSecret(string $value): void
    {
        $this->oauth_client_secret_encrypted = Crypt::encryptString($value);
    }

    public function getOAuthClientSecret(): ?string
    {
        return $this->oauth_client_secret_encrypted
            ? Crypt::decryptString($this->oauth_client_secret_encrypted)
            : null;
    }

    public function getEffectiveRedirectUri(): string
    {
        return $this->oauth_redirect_uri ?? url('/api/mail/callback');
    }

    // ── SMTP credentials ─────────────────────────────────────────────────────

    public function setSmtpUsername(string $value): void
    {
        $this->smtp_username_encrypted = Crypt::encryptString($value);
    }

    public function getSmtpUsername(): ?string
    {
        return $this->smtp_username_encrypted
            ? Crypt::decryptString($this->smtp_username_encrypted)
            : null;
    }

    public function setSmtpPassword(string $value): void
    {
        $this->smtp_password_encrypted = Crypt::encryptString($value);
    }

    public function getSmtpPassword(): ?string
    {
        return $this->smtp_password_encrypted
            ? Crypt::decryptString($this->smtp_password_encrypted)
            : null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isOAuth(): bool
    {
        return in_array($this->provider, ['gmail', 'outlook']);
    }

    public function isSmtp(): bool
    {
        return in_array($this->provider, ['zoho', 'yahoo', 'smtp']);
    }

    public function getProviderLabel(): string
    {
        return match ($this->provider) {
            'gmail'   => 'Gmail',
            'outlook' => 'Outlook / Microsoft 365',
            'zoho'    => 'Zoho Mail',
            'yahoo'   => 'Yahoo Mail',
            'smtp'    => 'Custom SMTP',
            default   => ucfirst($this->provider),
        };
    }

    /**
     * Safe representation for API responses — no secrets, shows configuration status.
     */
    public function toSafeArray(): array
    {
        return [
            'id'              => $this->id,
            'provider'        => $this->provider,
            'provider_label'  => $this->getProviderLabel(),
            'label'           => $this->label,
            'email'           => $this->email,
            'is_active'       => $this->is_active,
            'is_default'      => $this->is_default,
            'connected_at'    => $this->connected_at,
            'token_expired'   => $this->isOAuth() ? $this->isTokenExpired() : null,
            'is_oauth'        => $this->isOAuth(),
            'is_smtp'         => $this->isSmtp(),
            'smtp_host'       => $this->smtp_host,
            'smtp_port'       => $this->smtp_port,
            'smtp_encryption' => $this->smtp_encryption,
            'has_credentials' => $this->hasCredentials(),
            'scopes'          => $this->scopes,
        ];
    }

    private function hasCredentials(): bool
    {
        if ($this->isOAuth()) {
            return !empty($this->oauth_client_id_encrypted) && !empty($this->access_token_encrypted);
        }
        return !empty($this->smtp_username_encrypted) && !empty($this->smtp_password_encrypted);
    }
}
