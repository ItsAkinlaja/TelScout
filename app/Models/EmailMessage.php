<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    protected $fillable = [
        'user_id', 'opportunity_id', 'google_account_id',
        'recipient_email', 'recipient_name', 'subject',
        'body_html', 'body_text', 'status',
        'gmail_message_id', 'gmail_thread_id',
        'follow_up_count', 'follow_up_due_at',
        'sent_at', 'approved_at', 'failure_reason',
        'retry_count', 'meta',
    ];

    protected $casts = [
        'follow_up_due_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function googleAccount(): BelongsTo
    {
        return $this->belongsTo(GoogleAccount::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class)->orderBy('occurred_at');
    }
}
