<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Program extends Model
{
    use HasRelationships;

    protected $guarded = [];

    // protected $appends = ['client'];

    // Relationships

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // protected static function booted()
    // {
    //     static::creating(function ($request) {
    //         // If creating directly with submitted status (skip draft)
    //         if ($request->status === DPRStatus::Pending && empty($request->request_number)) {
    //             $request->request_number = self::generateRequestNumber();
    //         }
    //     });
    // }

    public function programCategory(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class);
    }

    public function partnershipContracts(): BelongsToMany
    {
        return $this->belongsToMany(
            PartnershipContract::class,
            'contract_program',
            'program_id',
            'partnership_contract_id'
        )->using(ContractProgram::class)->withTimestamps();
    }

    public function programActivities(): HasManyThrough
    {
        return $this->hasManyThrough(ProgramActivity::class, Coa::class);
    }

    public function programActivityItems(): HasManyDeep
    {
        return $this->hasManyDeep(ProgramActivityItem::class, [Coa::class, ProgramActivity::class]);
    }

    public function coas(): HasMany
    {
        return $this->hasMany(Coa::class);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('program_category_id', $categoryId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereHas('coas', function ($q) use ($year) {
            $q->where('contract_year', $year);
        });
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    public function getActiveYears(): array
    {
        return $this->coas()
            ->pluck('contract_year')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function getCoaForYear(int $year): ?COA
    {
        return $this->coas()->where('contract_year', $year)->first();
    }

    public function getTotalBudgetAllYears(): float
    {
        return $this->coas()->sum('budget_amount');
    }

    public function getTotalSpentAllYears(): float
    {
        return $this->coas()
            ->with('requestItems')
            ->get()
            ->sum(function ($coa) {
                return $coa->requestItems->sum('net_amount');
            });
    }

    public function getBudgetForYear(int $year): float
    {
        return $this->coas()
            ->where('contract_year', $year)
            ->sum('budget_amount');
    }

    public function getSpentForYear(int $year): float
    {
        $coaIds = $this->coas()
            ->where('contract_year', $year)
            ->pluck('id');

        return RequestItem::whereIn('coa_id', $coaIds)->sum('net_amount');
    }

    public function getMultiYearSummary(): array
    {
        $coas = $this->coas()->with('requestItems')->get();
        $summary = [];

        foreach ($coas as $coa) {
            $year = $coa->contract_year;
            if (!isset($summary[$year])) {
                $summary[$year] = [
                    'year' => $year,
                    'budget' => 0,
                    'spent' => 0,
                    'remaining' => 0,
                    'coa_count' => 0,
                ];
            }

            $spent = $coa->requestItems->sum('net_amount');
            $summary[$year]['budget'] += $coa->budget_amount;
            $summary[$year]['spent'] += $spent;
            $summary[$year]['remaining'] += ($coa->budget_amount - $spent);
            $summary[$year]['coa_count']++;
        }

        return array_values($summary);
    }
}
