<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalHistory extends Model
{
    protected $fillable = [
        'daily_payment_request_id',
        'approver_id',
        'sequence',
        'action',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'approved_at' => 'datetime',
        'action' => ApprovalAction::class
    ];

    // Relationships
    public function dailyPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(DailyPaymentRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    // Scopes
    public function scopePending(Builder $query): void
    {
        $query->where('action', ApprovalAction::Pending);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('action', ApprovalAction::Approved);
    }

    public function scopeRejected(Builder $query): void
    {
        $query->where('action', ApprovalAction::Rejected);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->action === ApprovalAction::Pending;
    }

    public function isApproved(): bool
    {
        return $this->action === ApprovalAction::Approved;
    }

    public function isRejected(): bool
    {
        return $this->action === ApprovalAction::Rejected;
    }

    public function approve(?string $notes = null): void
    {
        $this->action = ApprovalAction::Approved;
        $this->notes = $notes;
        $this->approved_at = now();
        $this->save();
    }

    public function reject(?string $notes = null): void
    {
        $this->action = ApprovalAction::Rejected;
        $this->notes = $notes;
        $this->approved_at = now();
        $this->save();
    }
}
