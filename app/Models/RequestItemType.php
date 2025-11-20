<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestItemType extends Model
{
    protected $fillable = [
        'name',
        'tax_id',
    ];

    // Relationships
    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
