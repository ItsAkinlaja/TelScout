<?php

namespace App\Models;

use App\Models\AuditLog;
use App\Models\AutomationSettings;
use App\Models\CandidateProfile;
use App\Models\EmailMessage;
use App\Models\FollowUp;
use App\Models\GoogleAccount;
use App\Models\MailAccount;
use App\Models\Opportunity;
use App\Models\SearchRun;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'google_id', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function googleAccounts(): HasMany
    {
        return $this->hasMany(GoogleAccount::class);
    }

    public function automationSettings(): HasOne
    {
        return $this->hasOne(AutomationSettings::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function searchRuns(): HasMany
    {
        return $this->hasMany(SearchRun::class);
    }

    public function mailAccounts(): HasMany
    {
        return $this->hasMany(MailAccount::class);
    }

    public function defaultMailAccount(): ?MailAccount
    {
        return $this->mailAccounts()->where('is_active', true)->orderByDesc('is_default')->first();
    }

    public function getGoogleAccount(): ?GoogleAccount
    {
        return $this->googleAccounts()->first();
    }
}
