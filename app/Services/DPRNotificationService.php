<?php

namespace App\Services;

use Exception;
use App\Enums\ApprovalAction;
use App\Mail\ApprovalRequestMail;
use App\Mail\ApprovalStepCompletedMail;
use App\Mail\RequestApprovedMail;
use App\Mail\RequestRejectedMail;
use App\Mail\RequestSubmittedMail;
use App\Models\ApprovalHistory;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Service class to handle Daily Payment Request email notifications
 *
 * This service is responsible for sending emails at various stages
 * of the DPR approval workflow.
 */
class DPRNotificationService
{
    /**
     * Send notification when a new request is submitted
     *
     * This notifies:
     * 1. The requester (confirmation)
     * 2. The first approver (action required)
     */
    public function sendRequestSubmittedNotification(DailyPaymentRequest $request): void
    {
        try {
            // 1. Send confirmation to requester
            Mail::to($request->requester->user->email)
                ->send(new RequestSubmittedMail($request));

            Log::info('Request submitted notification sent', [
                'request_id' => $request->id,
                'request_number' => $request->request_number,
                'requester' => $request->requester->user->email,
            ]);

            // 2. Send notification to first approver
            $this->notifyNextApprover($request);
        } catch (Exception $e) {
            Log::error('Failed to send request submitted notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify the next approver in the workflow
     */
    public function notifyNextApprover(DailyPaymentRequest $request): void
    {
        try {
            // Get the next pending approval (minimum sequence that's still pending)
            $nextApproval = $request->approvalHistories()
                ->where('action', ApprovalAction::Pending)
                ->orderBy('sequence')
                ->first();

            if (!$nextApproval) {
                Log::info('No pending approvals found', [
                    'request_id' => $request->id,
                ]);
                return;
            }

            $approver = $nextApproval->approver;
            $currentSequence = $nextApproval->sequence;
            $totalSteps = $request->approvalHistories()->count();
            $approvalPageUrl = $approver->jobTitle->code === 'FO' ? route('filament.admin.resources.daily-payment-requests.view', $request) :  URL::temporarySignedRoute('approve-payment-request', now(tz: 'Asia/Jakarta')->addDays(2), [
                'requestId' => $request->id,
                'approvalHistoryId' => $nextApproval->id,
            ]);

            // Send email to the approver
            Mail::to($approver->user->email)
                ->send(new ApprovalRequestMail(
                    $request,
                    $approver,
                    $currentSequence,
                    $totalSteps,
                    $approvalPageUrl
                ));

            Log::info('Approval request notification sent', [
                'request_id' => $request->id,
                'request_number' => $request->request_number,
                'approver' => $approver->user->email,
                'sequence' => $currentSequence,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send approval request notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when an approval action is taken (approved/rejected)
     *
     * This notifies:
     * 1. The requester (update on their request)
     * 2. The next approver (if applicable and approved)
     */
    public function sendApprovalActionNotification(
        DailyPaymentRequest $request,
        ApprovalHistory $approvalHistory
    ): void {
        try {
            $action = $approvalHistory->action;
            $approver = $approvalHistory->approver;

            // Check if this is the final approval or rejection
            $isFinalApproval = $this->isFinalApproval($request);
            $isRejected = $action === ApprovalAction::Rejected;

            if ($isFinalApproval && !$isRejected) {
                // All approvals complete - send final approval email
                $this->sendFinalApprovalNotification($request);
            } elseif ($isRejected) {
                // Request was rejected - send rejection email
                $this->sendRejectionNotification($request, $approvalHistory->notes ?? 'Tidak ada notes');
            } else {
                // Intermediate approval - notify requester and next approver
                $this->sendIntermediateApprovalNotification($request, $approver, $action);
            }
        } catch (Exception $e) {
            Log::error('Failed to send approval action notification', [
                'request_id' => $request->id,
                'approval_history_id' => $approvalHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification for intermediate approval step
     */
    protected function sendIntermediateApprovalNotification(
        DailyPaymentRequest $request,
        Employee $completedApprover,
        ApprovalAction $action
    ): void {
        // Get next approver if approved
        $nextApprover = null;
        if ($action === ApprovalAction::Approved) {
            $nextApproval = $request->approvalHistories()
                ->where('action', ApprovalAction::Pending)
                ->orderBy('sequence')
                ->first();

            $nextApprover = $nextApproval?->approver;

            // Notify next approver
            if ($nextApprover) {
                $this->notifyNextApprover($request);
            }
        }

        // Notify requester about the progress
        Mail::to($request->requester->user->email)
            ->send(new ApprovalStepCompletedMail(
                $request,
                $completedApprover,
                $action,
                $nextApprover
            ));

        Log::info('Intermediate approval notification sent', [
            'request_id' => $request->id,
            'action' => $action,
            'approver' => $completedApprover->user->email,
            'next_approver' => $nextApprover?->user->email,
        ]);
    }

    /**
     * Send notification when request is fully approved
     */
    protected function sendFinalApprovalNotification(DailyPaymentRequest $request): void
    {
        try {
            Mail::to($request->requester->user->email)
                ->send(new RequestApprovedMail($request));

            Log::info('Final approval notification sent', [
                'request_id' => $request->id,
                'request_number' => $request->request_number,
                'requester' => $request->requester->user->email,
            ]);

            // Optional: Notify finance team
            // $this->notifyFinanceTeam($request);
        } catch (Exception $e) {
            Log::error('Failed to send final approval notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when request is rejected
     */
    protected function sendRejectionNotification(DailyPaymentRequest $request, string $reason): void
    {
        try {
            Mail::to($request->requester->user->email)
                ->send(new RequestRejectedMail($request, $reason));

            Log::info('Rejection notification sent', [
                'request_id' => $request->id,
                'request_number' => $request->request_number,
                'requester' => $request->requester->user->email,
                'reason' => $reason,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send rejection notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify finance team when request is fully approved
     */
    protected function notifyFinanceTeam(DailyPaymentRequest $request): void
    {
        try {
            // // Get all finance department employees
            // $financeEmployees = Employee::whereHas('department', function ($query) {
            //     $query->where('code', 'FIN');
            // })->get();

            // foreach ($financeEmployees as $employee) {
            //     // You can create a specific Finance notification mail class
            //     // For now, we'll use the approved mail
            //     Mail::to($employee->email)
            //         ->send(new RequestApprovedMail($request, $employee));
            // }
            $HOFUser = Employee::whereHas('jobTitle', function ($query) {
                $query->where('code', 'HOF');
            })->get()->first();
            Mail::to($HOFUser->user->email)
                ->send(new RequestApprovedMail($request, $HOFUser));

            Log::info('Finance team notified', [
                'request_id' => $request->id,
                'finance_count' => 1,
                // 'finance_count' => $financeEmployees->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to notify finance team', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if all approvals are complete
     */
    protected function isFinalApproval(DailyPaymentRequest $request): bool
    {
        return $request->approvalHistories()
            ->where('action', ApprovalAction::Pending)
            ->count() === 0
            &&
            $request->approvalHistories()
            ->where('action', ApprovalAction::Approved)
            ->count() === $request->approvalHistories()->count();
    }

    /**
     * Send reminder to approver if no action taken within deadline
     *
     * This can be called via a scheduled task
     */
    public function sendApprovalReminder(ApprovalHistory $approvalHistory): void
    {
        try {
            if ($approvalHistory->action !== ApprovalAction::Pending) {
                return; // Already processed
            }

            $request = $approvalHistory->dailyPaymentRequest;
            $approver = $approvalHistory->approver;
            $currentSequence = $approvalHistory->sequence;
            $totalSteps = $request->approvalHistories()->count();

            // Check if this is still the current pending approval
            $isCurrentApprover = $request->approvalHistories()
                ->where('action', ApprovalAction::Pending)
                ->orderBy('sequence')
                ->first()
                ?->id === $approvalHistory->id;

            if (!$isCurrentApprover) {
                return; // Not their turn yet
            }

            $approvalPageUrl = URL::temporarySignedRoute('approve-payment-request', now(tz: 'Asia/Jakarta')->addDays(2), [
                'requestId' => $request->id,
                'approvalHistoryId' => $approvalHistory->id,
            ]);

            // Send reminder email
            Mail::to($approver->email)
                ->send(new ApprovalRequestMail(
                    $request,
                    $approver,
                    $currentSequence,
                    $totalSteps,
                    $approvalPageUrl
                ));

            Log::info('Approval reminder sent', [
                'request_id' => $request->id,
                'approver' => $approver->user->email,
                'days_pending' => $approvalHistory->created_at->diffInDays(now(tz: 'Asia/Jakarta')),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send approval reminder', [
                'approval_history_id' => $approvalHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk notifications to multiple approvers
     * Useful for cases where multiple people need to be notified
     */
    public function sendBulkNotification(DailyPaymentRequest $request, array $employeeIds, string $message): void
    {
        try {
            $employees = Employee::whereIn('id', $employeeIds)->get();

            foreach ($employees as $employee) {
                // Send custom notification
                // You can create a generic notification mail class
                Mail::to($employee->email)
                    ->send(new RequestSubmittedMail($request, $employee)); // Adjust as needed
            }

            Log::info('Bulk notification sent', [
                'request_id' => $request->id,
                'recipient_count' => $employees->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send bulk notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
