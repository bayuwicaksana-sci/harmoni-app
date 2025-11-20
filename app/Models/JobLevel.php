<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobLevel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'level' => 'integer'
    ];

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    public function approvalRules(): HasMany
    {
        return $this->hasMany(ApprovalRule::class);
    }
}
