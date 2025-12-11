<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Models\ApprovalHistory;
use App\Models\ApprovalWorkflow;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalService
{
    /**
     * Submit request and create approval histories
     */
    public function submitRequest(DailyPaymentRequest $paymentRequest): void
    {
        Log::info("\n\n\n\nApproval Workflow Begin for : ".$paymentRequest->request_number."\n\n");
        DB::transaction(
            function () use ($paymentRequest) {
                // Get active workflow
                $workflow = ApprovalWorkflow::active()->first();

                if (! $workflow) {
                    throw new Exception('No active approval workflow found');
                }

                // Get requester
                $paymentRequester = $paymentRequest->requester;

                // Get applicable rules based on amount
                $rules = $workflow->getApplicableRules($paymentRequest->total_request_amount);

                $sequence = 1;
                $approvalHistories = collect();
                $usedApproverIds = collect();

                foreach ($rules as $rule) {
                    // Get eligible approvers based on rule type
                    $approvers = $rule->getEligibleApprovers($paymentRequester);

                    Log::info("Rule {$rule->sequence} ({$rule->approver_type->value}): Found ".$approvers->count().' approver(s)');

                    // Skip if no approvers found
                    if ($approvers->isEmpty()) {
                        Log::info("Skipping rule {$rule->sequence} - no eligible approvers found");

                        continue;
                    }

                    // Filter out:
                    // 1. Self-approval (requester can't approve their own request)
                    // 2. Duplicate approvers (same person already in the chain)
                    $validApprovers = $approvers->filter(function ($approver) use ($paymentRequester, $usedApproverIds) {
                        return $approver->id !== $paymentRequester->id
                            && ! $usedApproverIds->contains($approver->id);
                    });

                    // If no valid approvers after filtering, skip this rule
                    if ($validApprovers->isEmpty()) {
                        Log::info("Skipping rule {$rule->sequence} - would result in self-approval or duplicate approver");

                        continue;
                    }

                    // For supervisor type, there should be exactly one
                    // For job_level or job_title, pick the first (or implement logic to choose)
                    $approver = $approvers->first();

                    // Track this approver to prevent duplicates
                    $usedApproverIds->push($approver->id);

                    // Create approval history
                    // ApprovalHistory::create([
                    //     'daily_payment_request_id' => $paymentRequest->id,
                    //     'approver_id' => $approver->id,
                    //     'sequence' => $rule->sequence,
                    //     'action' => ApprovalAction::Pending,
                    // ]);

                    // Create approval history
                    $approvalHistories->push([
                        'daily_payment_request_id' => $paymentRequest->id,
                        'sequence' => $sequence,
                        'approver_id' => $approver->id,
                        'action' => ApprovalAction::Pending,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $sequence++;
                }

                // Update request status to pending
                // $paymentRequest->update(['status' => 'pending']);
                // Validate: Must have at least 1 approver
                if ($approvalHistories->isEmpty()) {
                    throw new Exception(
                        'Cannot submit request: No valid approvers found. '.
                            'This request would either result in self-approval or has no eligible approvers in the system. '.
                            'Please contact the administrator.'
                    );
                }

                // Insert all approval histories
                ApprovalHistory::insert($approvalHistories->toArray());
                Log::info("\n\nApproval Workflow Ended for : ".$paymentRequest->request_number."\n\n\n\n");

                // TODO: Send notifications to approvers
                $notificationService = app(DPRNotificationService::class);
                $notificationService->sendRequestSubmittedNotification($paymentRequest);
            }
        );
    }

    /**
     * Approve a request
     */
    public function approve(ApprovalHistory $approval, ?string $notes = null): void
    {
        // Update approval history
        $approval->approve($notes);

        $request = $approval->dailyPaymentRequest;

        // $request = $approval->dailyPaymentRequest;

        // $hasRejected = $request->approvalHistories()
        //     ->where('action', ApprovalAction::Rejected)
        //     ->exists();

        // if ($hasRejected) {
        //     // If any rejected, mark request as rejected
        //     $request->update(['status' => ApprovalAction::Rejected]);
        //     return;
        // }

        $pendingCount = $request->approvalHistories()
            ->where('action', ApprovalAction::Pending)
            ->count();

        Log::info("\n\n\n Pending Count : ".$pendingCount."\n\n");

        if ($pendingCount === 0) {
            // All approved
            $request->update(['status' => DPRStatus::Approved]);
            $request->requestItems()->wherePaymentType(RequestPaymentType::Reimburse)->update(['status' => RequestItemStatus::Closed]);
            $request->requestItems()->wherePaymentType(RequestPaymentType::Offset)->update(['status' => RequestItemStatus::Closed]);
            $request->requestItems()->wherePaymentType(RequestPaymentType::Advance)->update(['status' => RequestItemStatus::WaitingSettlement, 'due_date' => now()->addDays(3)->toDateString()]);
        }

        $notificationService = app(DPRNotificationService::class);
        $notificationService->sendApprovalActionNotification($request, $approval);

        //     // TODO: Notify requester

        // } else {
        //     // Still has pending approvals
        //     // TODO: Notify next approver
        //     $this->notifyNextApprover($request);
        // }
    }

    /**
     * Reject a request
     */
    public function reject(ApprovalHistory $approval, string $notes): void
    {
        // Update approval history
        $approval->reject($notes);

        // Update request status to rejected
        $request = $approval->dailyPaymentRequest;
        $request->update(['status' => DPRStatus::Rejected]);

        $request->requestItems()->update(['status' => RequestItemStatus::Rejected, 'settlement_receipt_id' => null]);

        $request->approvalHistories()->where('action', ApprovalAction::Pending)->update(['action' => ApprovalAction::Cancelled]);
        $request->refresh();

        // TODO: Notify requester
        $notificationService = app(DPRNotificationService::class);
        $notificationService->sendApprovalActionNotification($request, $approval);
    }

    /**
     * Get pending approvals for an employee
     */
    public function getPendingApprovalsForEmployee(Employee $employee): Collection
    {
        return ApprovalHistory::where('approver_id', $employee->id)
            ->where('action', ApprovalAction::Pending)
            ->with(['dailyPaymentRequest.requester', 'dailyPaymentRequest.requestItems'])
            ->get();
    }

    /**
     * Check if employee can approve a specific request
     */
    public function canEmployeeApprove(Employee $employee, DailyPaymentRequest $request): bool
    {
        return ApprovalHistory::where('daily_payment_request_id', $request->id)
            ->where('approver_id', $employee->id)
            ->where('action', ApprovalAction::Pending)
            ->exists();
    }

    // // TODO: Implement notification methods
    // protected function notifyApprovers(DailyPaymentRequest $request): void
    // {
    //     // $notificationService = new DPRNotificationService();
    //     // $notificationService->not
    // }

    // protected function notifyNextApprover(DailyPaymentRequest $request): void
    // {
    //     // Notify the next approver in sequence
    // }

    // protected function notifyRequester(DailyPaymentRequest $request, ApprovalAction $status): void
    // {
    //     // Notify requester about approval/rejection
    // }
}
