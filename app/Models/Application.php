<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'opportunity_id', 'status', 'sort_order',
        'notes', 'interview_dates', 'applied_at',
    ];

    protected $casts = [
        'interview_dates' => 'array',
        'applied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest();
    }
}
