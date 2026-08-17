<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class GoogleAccount extends Model
{
    protected $fillable = [
        'user_id', 'email', 'provider',
        'access_token_encrypted', 'refresh_token_encrypted',
        'token_expires_at', 'scopes', 'connected_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setAccessToken(string $token): void
    {
        $this->access_token_encrypted = Crypt::encryptString($token);
    }

    public function getAccessToken(): ?string
    {
        if (!$this->access_token_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->access_token_encrypted);
    }

    public function setRefreshToken(string $token): void
    {
        $this->refresh_token_encrypted = Crypt::encryptString($token);
    }

    public function getRefreshToken(): ?string
    {
        if (!$this->refresh_token_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->refresh_token_encrypted);
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }
        return $this->token_expires_at->isPast();
    }
}
