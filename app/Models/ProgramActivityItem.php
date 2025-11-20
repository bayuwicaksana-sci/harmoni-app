<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ProgramActivityItem extends Model
{
    protected $fillable = [
        // 'code',
        'program_activity_id',
        'description',
        'volume',
        'unit',
        'frequency',
        'total_item_budget',
        'total_item_planned_budget',
        'is_completed'
    ];

    protected $casts = [
        'volume' => 'integer',
        'frequency' => 'integer',
        'total_item_budget' => 'decimal:2',
        'total_item_planned_budget' => 'decimal:2',
        'is_completed' => 'boolean'
    ];

    public function programActivity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class);
    }

    public function program(): HasOneThrough
    {
        return $this->hasOneThrough(Program::class, ProgramActivity::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('is_completed', true);
    }
}
