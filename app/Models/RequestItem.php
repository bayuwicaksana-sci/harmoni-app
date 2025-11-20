<?php

namespace App\Models;

use App\Enums\COAType;
use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Enums\TaxMethod;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use function PHPUnit\Framework\isNull;

class RequestItem extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'daily_payment_request_id',
        'coa_id',
        'program_activity_id',
        'program_activity_item_id',
        'payment_type',
        'advance_percentage',
        'quantity',
        'unit_quantity',
        'amount_per_item',
        'request_item_type_id',
        'tax_method',
        // 'amount',
        // 'tax_amount',
        // 'net_amount',
        'description',
        // Snapshot fields
        'coa_code',
        'coa_name',
        'coa_type',
        'program_id',
        'program_name',
        'program_code',
        'program_category_name',
        'contract_year',
        'tax_type',
        'tax_rate',
        'item_type_name',
        'settled_at',
        'self_account',
        'bank_name',
        'bank_account',
        'account_owner',
        'status',
        'tax_id',
        'is_taxed'
    ];

    protected $casts = [
        'advance_percentage' => 'decimal:2',
        'quantity' => 'decimal:2',
        'amount_per_item' => 'decimal:2',
        // 'amount' => 'decimal:2',
        // 'tax_amount' => 'decimal:2',
        // 'net_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'contract_year' => 'integer',
        'payment_type' => RequestPaymentType::class,
        'tax_method' => TaxMethod::class,
        'coa_type' => COAType::class,
        'self_account' => 'boolean',
        'status' => RequestItemStatus::class,
        'is_taxed' => 'boolean'
    ];

    protected $appends = ['total_amount', 'tax_amount', 'net_amount'];

    protected static function booted()
    {
        static::updating(function ($request) {
            if (
                $request->isDirty('is_taxed')
            ) {
                if (!$request->is_taxed) {
                    $request->tax_id = null;
                }
            }
        });
    }


    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function dailyPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(DailyPaymentRequest::class);
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class);
    }

    public function requestItemType(): BelongsTo
    {
        return $this->belongsTo(RequestItemType::class);
    }

    public function programSnapshot(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function programActivity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class);
    }

    public function programActivityItem(): BelongsTo
    {
        return $this->belongsTo(ProgramActivityItem::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    protected function totalAmount(): Attribute
    {
        return new Attribute(
            get: function () {
                // Handle null values
                if (is_null($this->quantity) || is_null($this->amount_per_item)) {
                    return 0;
                }

                return $this->quantity * $this->amount_per_item;
            },
        );
    }

    protected function taxAmount(): Attribute
    {
        return new Attribute(
            get: function () {
                // Handle null values
                if (!$this->is_taxed || is_null($this->tax_id)) {
                    return null;
                }

                return $this->total_amount * $this->tax->value;
            },
        );
    }

    protected function netAmount(): Attribute
    {
        return new Attribute(
            get: function () {
                // Handle null values
                if (!$this->is_taxed || is_null($this->tax_id) || is_null($this->tax_method)) {
                    return null;
                }

                $netAmount = 0;

                if ($this->tax_method === TaxMethod::ToSCI) {
                    $netAmount += $this->total_amount - ($this->total_amount * $this->tax->value);
                } else {
                    $netAmount += $this->total_amount + ($this->total_amount * $this->tax->value);
                }


                return $netAmount;
            },
        );
    }

    // ============================================
    // BOOT - AUTO SNAPSHOTS
    // ============================================

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($requestItem) {
    //         $requestItem->createSnapshots();
    //     });
    // }

    public function createSnapshots(): void
    {
        // if ($this->coa_id && !$this->coa_code) {
        if ($this->coa_id) {
            $coa = Coa::with(['program.programCategory'])->find($this->coa_id);

            if ($coa) {
                $this->coa_code = $coa->code;
                $this->coa_name = $coa->name;
                $this->coa_type = $coa->type;

                if ($coa->program_id && $coa->program) {
                    $program = $coa->program;

                    $this->program_id = $program->id;
                    $this->program_name = $program->name;
                    $this->program_code = $program->code;

                    if ($program->programCategory) {
                        $this->program_category_name = $program->programCategory->name;
                    }

                    // Use COA's contract year
                    $this->contract_year = $coa->contract_year;
                }
            }
        }

        // if ($this->request_item_type_id && !$this->tax_type) {
        if ($this->request_item_type_id) {
            $itemType = RequestItemType::find($this->request_item_type_id);

            if ($itemType) {
                $this->tax_type = $itemType->tax->name;
                $this->tax_rate = $itemType->tax->value;
                $this->item_type_name = $itemType->name;
            }
        }

        $this->save();
    }

    // ============================================
    // LOCAL SCOPE METHODS
    // ============================================
    #[Scope]
    protected function waitingPayment(Builder $query): void
    {
        $query->where('status', RequestItemStatus::WaitingPayment);
    }

    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('status', RequestItemStatus::Paid);
    }

    #[Scope]
    protected function waitingSettlement(Builder $query): void
    {
        $query->where('status', RequestItemStatus::WaitingSettlement);
    }

    #[Scope]
    protected function settled(Builder $query): void
    {
        $query->where('status', RequestItemStatus::Settled);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    public function getDisplayNameAttribute(): string
    {
        if ($this->contract_year) {
            return "{$this->coa_name} ({$this->contract_year})";
        }
        return $this->coa_name ?? $this->coa_code ?? 'Unknown';
    }

    public function isAdvancePayment(): bool
    {
        return $this->payment_type === RequestPaymentType::Advance;
    }

    public function isReimbursement(): bool
    {
        return $this->payment_type === RequestPaymentType::Reimburse;
    }
}
