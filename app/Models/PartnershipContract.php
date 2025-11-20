<?php

namespace App\Models;

use App\Enums\PartnershipContractStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use phpDocumentor\Reflection\Types\This;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class PartnershipContract extends Model
{
    use HasRelationships;

    protected $guarded = [];

    protected $casts = [
        'contract_year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        // 'status' => PartnershipContractStatus::class
    ];

    // protected static function booted()
    // {
    //     static::creating(function ($model) {
    //         if (!$model->contract_number) {
    //             $model->contract_number = $model->generateContractNumber();
    //         }
    //     });
    // }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function coas(): HasMany
    {
        return $this->hasMany(Coa::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            Program::class,
            'contract_program',
            'partnership_contract_id',
            'program_id'
        )->using(ContractProgram::class)->withTimestamps();
    }

    public function programActivityItems(): HasManyDeep
    {
        return $this->hasManyDeep(
            ProgramActivityItem::class,
            [ContractProgram::class, Program::class, Coa::class, ProgramActivity::class],
            [
                'partnership_contract_id',      // Foreign key on the pivot table
                'id',       // Foreign key on chart_of_accounts table
                'program_id', // Foreign key on program_tasks table
                'coa_id',   // Foreign key on program_task_items table
                'program_activity_id',   // Foreign key on program_task_items table
            ],
            [
                'id',               // Local key on contracts table
                'program_id',               // Local key on pivot table (program_id)
                'id',               // Local key on programs table
                'id',                // Local key on chart_of_accounts table
                'id',                // Local key on chart_of_accounts table
            ]
        );
    }

    protected function contractValue(): Attribute
    {
        return new Attribute(
            get: fn() => (float) $this->programActivityItems->sum('total_item_budget'),
        );
    }

    protected function plannedValue(): Attribute
    {
        return new Attribute(
            get: fn() => (float) $this->programActivityItems->sum('total_item_planned_budget'),
        );
    }

    // Accessors & Mutators
    // protected function isActive(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn() => now()->between($this->start_date, $this->end_date),
    //     );
    // }

    // // Get total allocated budget for this contract
    // public function getTotalAllocatedBudget(): float
    // {
    //     return $this->programs()->sum('pc_program.budget_allocation');
    // }

    // // Get total realized amount for this contract
    // public function getTotalRealized(): float
    // {
    //     return $this->programs()->sum('pc_program.realized');
    // }

    // // Get remaining unallocated budget
    // public function getUnallocatedBudget(): float
    // {
    //     return $this->total_contract_value - $this->getTotalAllocatedBudget();
    // }

    // // Attach a program with budget allocation
    // public function attachProgram(Program $program, array $data): void
    // {
    //     // When attaching a program to a contract, create/update the yearly CoA
    //     $this->programs()->attach($program->id, $data);

    //     $program->createOrUpdateYearlyCoA(
    //         $this->contract_year,
    //         $data['budget_allocation']
    //     );
    // }

    // protected function generateContractNumber(): string
    // {
    //     $year = $this->contract_year;
    //     $clientCode = $this->client->code;
    //     $sequence = static::where('client_id', $this->client_id)
    //         ->where('contract_year', $year)
    //         ->count() + 1;

    //     return "PC-{$clientCode}-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    // }
}
