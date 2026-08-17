<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySource extends Model
{
    protected $fillable = ['company_id', 'source', 'source_url', 'raw_data'];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
