<?php

namespace App\Models;

use App\Enums\RequestItemStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\JoinClause;

class Employee extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function dailyPaymentRequests(): HasMany
    {
        return $this->hasMany(DailyPaymentRequest::class, 'requester_id');
    }

    public function requestItems(): HasManyThrough
    {
        return $this->hasManyThrough(RequestItem::class, DailyPaymentRequest::class, 'requester_id');
    }

    public function disbursedRequestItem()
    {
        $excludedStatuses = [
            RequestItemStatus::Draft,
            RequestItemStatus::WaitingPayment,
            RequestItemStatus::WaitingApproval,
            RequestItemStatus::Rejected,
        ];

        return $this->requestItems()->whereNotIn('request_items.status', $excludedStatuses);
    }

    public function closedRequest()
    {
        return $this->requestItems()->where('request_items.status', RequestItemStatus::Closed);
    }

    public function ontimeRequest()
    {
        return $this->closedRequest()
            // ->join('settlement_receipts', 'request_items.settlement_receipt_id', '=', 'settlement_receipts.id')
            ->join('settlement_receipts', function (JoinClause $join) {
                $join->on('request_items.settlement_receipt_id', '=', 'settlement_receipts.id')
                    ->where('settlement_receipts.realization_date', '>=', 'request_items.due_date');
            })
            ->select('request_items.*');
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approver_id');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    // Helper Methods
    public function getJobLevel(): int
    {
        return $this->jobTitle->jobLevel->level;
    }

    public function canApprove(int $requiredLevel): bool
    {
        return $this->getJobLevel() >= $requiredLevel;
    }

    public function hasSupervisor(): bool
    {
        return ! is_null($this->supervisor_id);
    }

    public function hasSubordinates(): bool
    {
        return $this->subordinates()->exists();
    }

    // Attribute
    public function settlementHealthIndexScore(): Attribute
    {
        return new Attribute(
            get: function () {
                // Cache the counts to avoid multiple queries
                $closedCount = $this->closedRequest()->count();
                $ontimeCount = $this->ontimeRequest()->count();

                // Fixed calculation with proper parentheses
                if ($closedCount > 0) {
                    return ($ontimeCount / $closedCount) * 100;
                }

                return null;
            }
        );
    }
}
