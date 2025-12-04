<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramActivity extends Model
{
    protected $fillable = [
        'code',
        'name',
        'coa_id',
        'est_start_date',
        'est_end_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'est_start_date' => 'date',
        'est_end_date' => 'date',
    ];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }

    // public function program(): BelongsTo
    // {
    //     return $this->belongsTo(Program::class);
    // }

    public function programActivityItems(): HasMany
    {
        return $this->hasMany(ProgramActivityItem::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', 1);
    }
}
