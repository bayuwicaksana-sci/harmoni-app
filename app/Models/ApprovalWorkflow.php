<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $with = ['approvalRules'];

    // Relationships
    public function approvalRules(): HasMany
    {
        return $this->hasMany(ApprovalRule::class)->orderBy('sequence');
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // Helper Methods
    public function getApplicableRules(float $amount): Collection
    {
        return $this->approvalRules->filter(function ($rule) use ($amount) {
            if ($rule->condition_type === 'always') {
                return true;
            }
            return $amount >= $rule->condition_value;
        });
    }
}
