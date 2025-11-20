<?php

namespace App\Models;

use App\Enums\COAType;
use App\Enums\DPRStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coa extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'partnership_contract_id',
        'program_id',
        'budget_amount',
        'planned_budget',
        'contract_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => COAType::class,
        'budget_amount' => 'decimal:2',
        'planned_budget' => 'decimal:2',
        'contract_year' => 'integer',
    ];

    // Relationships
    public function partnershipContract(): BelongsTo
    {
        return $this->belongsTo(PartnershipContract::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    public function programActivities(): HasMany
    {
        return $this->hasMany(ProgramActivity::class);
    }

    protected function coaProgramCategory(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->program ? $this->program->programCategory->name : 'Non-Program',
        );
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProgramType($query)
    {
        return $query->where('type', COAType::Program);
    }

    public function scopeNonProgramType($query)
    {
        return $query->where('type', COAType::NonProgram);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('contract_year', $year);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    public function getTotalSpent(): float
    {
        return $this->requestItems()->whereHas('dailyPaymentRequest', function ($query) {
            $query->where('status', DPRStatus::Approved);
        })->sum('amount');
        // return $this->requestItems()->sum('net_amount');
    }

    public function getRemainingBudget(): float
    {
        return $this->budget_amount - $this->getTotalSpent();
    }

    public function getBudgetUtilization(): float
    {
        if ($this->budget_amount == 0) {
            return 0;
        }

        return ($this->getTotalSpent() / $this->budget_amount) * 100;
    }

    public function isBudgetExceeded(): bool
    {
        return $this->getTotalSpent() > $this->budget_amount;
    }

    public function getBudgetStatus(): string
    {
        $utilization = $this->getBudgetUtilization();

        if ($utilization > 100) {
            return 'exceeded';
        }
        if ($utilization >= 90) {
            return 'critical';
        }
        if ($utilization >= 75) {
            return 'warning';
        }

        return 'healthy';
    }

    public function getFullNameAttribute(): string
    {
        if ($this->contract_year) {
            return "{$this->name} ({$this->contract_year})";
        }

        return $this->name;
    }
}
