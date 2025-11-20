<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approver_id');
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
}
