<?php

namespace App\Observers;

use App\Enums\ApprovalAction;
use App\Enums\DPRStatus;
use App\Enums\SettlementStatus;
use App\Models\DailyPaymentRequest;
use App\Models\Settlement;
use App\Services\SettlementNotificationService;
use Illuminate\Support\Facades\DB;

class DailyPaymentRequestObserver
{
    /**
     * Handle the DailyPaymentRequest "created" event.
     */
    public function created(DailyPaymentRequest $dailyPaymentRequest): void
    {
        //
    }

    /**
     * Handle the DailyPaymentRequest "updated" event.
     */
    public function updated(DailyPaymentRequest $dailyPaymentRequest): void
    {
        // Wrap in transaction (Fix #6)
        DB::transaction(function () use ($dailyPaymentRequest) {
            // Check if status changed to Approved
            if ($dailyPaymentRequest->wasChanged('status') && $dailyPaymentRequest->status === DPRStatus::Approved) {
                // Find related Settlement waiting for this DPR approval (Fix #3 - use new relationship)
                $settlement = $dailyPaymentRequest->settlement()
                    ->where('status', SettlementStatus::WaitingDPRApproval)
                    ->first();

                if ($settlement) {
                    // Auto-approve settlement now that DPR is approved
                    $settlement->approve();
                }
            }

            // Check if status changed to Rejected
            if ($dailyPaymentRequest->wasChanged('status') && $dailyPaymentRequest->status === DPRStatus::Rejected) {
                // Find related Settlement (Fix #3 - use new relationship)
                $settlement = $dailyPaymentRequest->settlement()
                    ->where('status', SettlementStatus::WaitingDPRApproval)
                    ->first();

                if ($settlement) {
                    // Fix #2: Safe navigation to prevent NULL pointer
                    $rejectionNotes = $dailyPaymentRequest->approvalHistories()
                        ->where('action', ApprovalAction::Rejected)
                        ->first()?->notes ?? 'Tidak ada alasan';

                    // Route back to Draft for revision
                    $settlement->update([
                        'status' => SettlementStatus::Rejected,
                        'previous_status' => SettlementStatus::WaitingDPRApproval,
                        'revision_notes' => 'DPR ditolak: '.$rejectionNotes,
                    ]);

                    // Notify submitter
                    app(SettlementNotificationService::class)
                        ->notifySubmitterOfRevision($settlement);
                }
            }
        });
    }

    /**
     * Handle the DailyPaymentRequest "deleted" event.
     */
    public function deleted(DailyPaymentRequest $dailyPaymentRequest): void
    {
        //
    }

    /**
     * Handle the DailyPaymentRequest "restored" event.
     */
    public function restored(DailyPaymentRequest $dailyPaymentRequest): void
    {
        //
    }

    /**
     * Handle the DailyPaymentRequest "force deleted" event.
     */
    public function forceDeleted(DailyPaymentRequest $dailyPaymentRequest): void
    {
        //
    }
}
