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
