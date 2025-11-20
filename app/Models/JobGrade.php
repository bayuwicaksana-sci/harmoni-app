<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGrade extends Model
{
    protected $guarded = [];

    protected $casts = [
        'numeric_value' => 'integer'
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
