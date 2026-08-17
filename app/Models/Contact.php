<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'company_id', 'name', 'email', 'role', 'contact_type',
        'source_url', 'verified', 'opted_out', 'opted_out_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
