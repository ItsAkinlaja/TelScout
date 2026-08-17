<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationNote extends Model
{
    protected $fillable = ['application_id', 'content'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
