<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    protected $fillable = ['name', 'code'];

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(Employee::class, JobTitle::class);
    }
}
