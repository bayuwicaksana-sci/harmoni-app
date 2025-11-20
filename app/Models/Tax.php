<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    protected $fillable = [
        'code',
        'name',
        'value'
    ];

    protected $casts = [
        'value' => 'decimal:2'
    ];

    public function requestItemTypes(): HasMany
    {
        return $this->hasMany(RequestItemType::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }
}
