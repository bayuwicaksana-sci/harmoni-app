<?php

namespace App\Observers;

use App\Enums\DPRStatus;
use App\Enums\SettlementStatus;
use App\Models\DailyPaymentRequest;
use App\Models\Settlement;

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
        // Check if status changed to Approved
        if ($dailyPaymentRequest->wasChanged('status') && $dailyPaymentRequest->status === DPRStatus::Approved) {
            // Find related Settlement waiting for this DPR approval
            $settlement = Settlement::where('generated_payment_request_id', $dailyPaymentRequest->id)
                ->where('status', SettlementStatus::WaitingDPRApproval)
                ->first();

            if ($settlement) {
                // Auto-approve settlement now that DPR is approved
                $settlement->approve();
            }
        }

        // Check if status changed to Rejected
        if ($dailyPaymentRequest->wasChanged('status') && $dailyPaymentRequest->status === DPRStatus::Rejected) {
            $settlement = Settlement::where('generated_payment_request_id', $dailyPaymentRequest->id)
                ->where('status', SettlementStatus::WaitingDPRApproval)
                ->first();

            if ($settlement) {
                // Route back to Draft for revision
                $settlement->update([
                    'status' => SettlementStatus::Draft,
                    'previous_status' => SettlementStatus::WaitingDPRApproval,
                    'revision_notes' => 'DPR ditolak: '.($dailyPaymentRequest->rejection_reason ?? 'Tidak ada alasan'),
                ]);

                // Notify submitter
                app(\App\Services\SettlementNotificationService::class)
                    ->notifySubmitterOfRevision($settlement);
            }
        }
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
