<?php

namespace App\Services;

use App\Enums\DPRStatus;
use App\Enums\RequestPaymentType;
use App\Enums\SettlementStatus;
use App\Models\DailyPaymentRequest;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;

class SettlementDPRService
{
    /**
     * Check if settlement requires DPR based on current items
     * DPR is required for: unplanned items, offset items, reimbursement items
     * But NOT for items that have been internally reconciled within same COA
     */
    public function requiresDPR(Settlement $settlement): bool
    {
        return $settlement->settlementItems()
            ->where(function ($query) {
                $query->where('is_unplanned', true)
                    ->orWhere('payment_type', RequestPaymentType::Offset)
                    ->orWhere('payment_type', RequestPaymentType::Reimburse);
            })
            ->exists();
    }

    /**
     * Check if settlement requires DPR based on processed results
     * This method should be called after settlement processing to determine if DPR is needed
     */
    public function requiresDPRFromResults(array $results): bool
    {
        // Check if there are any unplanned items
        if (! empty($results['categorized']['new'])) {
            return true;
        }

        // Check if there are any reimbursement items that weren't internally reconciled
        if (! empty($results['reimbursement_items'])) {
            return true;
        }

        // Check if there are any offset items for new items (not same-COA reconciliation)
        if (! empty($results['offset_items'])) {
            return true;
        }

        return false;
    }

    /**
     * Create DPR for settlement with DB transaction
     * Links existing settlement items to new DPR
     */
    public function createDPRForSettlement(Settlement $settlement): DailyPaymentRequest
    {
        return DB::transaction(function () use ($settlement) {
            // Collect items requiring DPR approval
            $dprItems = $settlement->settlementItems()
                ->where(function ($query) {
                    $query->where('is_unplanned', true)
                        ->orWhere('payment_type', RequestPaymentType::Offset)
                        ->orWhere('payment_type', RequestPaymentType::Reimburse);
                })
                ->get();

            // Create DPR
            $dpr = DailyPaymentRequest::create([
                'request_date' => $settlement->submit_date ?? now(),
                'status' => DPRStatus::Pending,
                'requester_id' => $settlement->submitter_id,
            ]);

            // Link items to DPR
            foreach ($dprItems as $item) {
                $item->update(['daily_payment_request_id' => $dpr->id]);
            }

            // Submit through approval workflow
            app(\App\Services\ApprovalService::class)->submitRequest($dpr);

            // Update settlement
            $settlement->update([
                'generated_payment_request_id' => $dpr->id,
                'status' => SettlementStatus::WaitingDPRApproval,
            ]);

            return $dpr;
        });
    }
}
