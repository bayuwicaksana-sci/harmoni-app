<?php

namespace App\Models;

use Illuminate\Support\Collection;
use App\Enums\ApproverType;
use App\Enums\DPRApprovalRuleCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRule extends Model
{
    protected $fillable = [
        'approval_workflow_id',
        'sequence',
        'condition_type',
        'condition_value',
        'approver_type', // NEW
        'approver_job_level_id',
        'approver_job_title_id', // NEW
    ];

    protected $casts = [
        'sequence' => 'integer',
        'condition_value' => 'decimal:2',
        'approver_type' => ApproverType::class,
        'condition_type' => DPRApprovalRuleCondition::class
    ];

    // Relationships
    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }

    // NEW: Job Level relationship
    public function approverJobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class, 'approver_job_level_id');
    }

    // NEW: Job Title relationship
    public function approverJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'approver_job_title_id');
    }

    // Helper Methods
    public function getEligibleApprovers(?Employee $requester = null): Collection
    {
        switch ($this->approver_type) {
            case ApproverType::Supervisor:
                // Return requester's supervisor
                if ($requester && $requester->supervisor) {
                    return collect([$requester->supervisor]);
                }
                return collect();

            case ApproverType::JobLevel:
                // Find all employees with required job level
                return Employee::whereHas('jobTitle', function ($query) {
                    $query->where('jobTitle.jobLevel.id', $this->approver_job_level_id);
                })->get();

            case ApproverType::JobTitle:
                // Find employees with specific job title
                return Employee::where('job_title_id', $this->approver_job_title_id)->get();

            default:
                return collect();
        }
    }

    public function isApplicable(float $amount): bool
    {
        if ($this->condition_type === DPRApprovalRuleCondition::Always) {
            return true;
        }
        return $amount >= $this->condition_value;
    }

    public function getApproverDescription(): string
    {
        switch ($this->approver_type) {
            case ApproverType::Supervisor:
                return "Requester's Supervisor";

            case ApproverType::JobLevel:
                return "Job Level {$this->approverJobLevel->level}";

            case ApproverType::JobTitle:
                return $this->approverJobTitle?->title ?? 'Unknown Job Title';

            default:
                return 'Unknown';
        }
    }
}
