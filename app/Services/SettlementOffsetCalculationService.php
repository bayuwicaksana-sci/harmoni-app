<?php

namespace App\Services;

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Models\RequestItem;
use App\Models\Settlement;

class SettlementOffsetCalculationService
{
    /**
     * Calculate FIFO-based offsets per COA
     * Exact algorithm from CreateSettlement (preserved)
     */
    public function calculateOffsets(array $categorized, Settlement $settlement): array
    {
        $offsetItems = [];
        $totalRefundAmount = 0;

        // Get all COAs that have available funds (cancelled or variance)
        $allCoasWithFunds = array_unique(array_merge(
            array_keys($categorized['cancelled'] ?? []),
            array_keys($categorized['variance'] ?? [])
        ));

        foreach ($allCoasWithFunds as $coaId) {
            $cancelled = $categorized['cancelled'][$coaId] ?? [];
            $variance = $categorized['variance'][$coaId] ?? [];
            $newItems = $categorized['new'][$coaId] ?? [];

            // Calculate totals
            $cancelledTotal = collect($cancelled)->sum('amount');
            $varianceTotal = collect($variance)->sum('variance');
            $availableFunds = $cancelledTotal + $varianceTotal;

            $newTotal = collect($newItems)->sum('total_price');

            $offsetNeeded = min($availableFunds, $newTotal);
            $offsetRemaining = $offsetNeeded;

            // Allocate offsets from cancelled items first (FIFO)
            foreach ($cancelled as $index => $cancelledData) {
                if ($offsetRemaining <= 0) {
                    break;
                }

                $cancelledItem = $cancelledData['item'];
                $amount = $cancelledData['amount'];

                $useAmount = min($amount, $offsetRemaining);

                if ($useAmount > 0) {
                    // Create offset request item
                    $offsetItem = new RequestItem([
                        'settlement_id' => $settlement->id,
                        'coa_id' => $coaId,
                        'program_activity_id' => $cancelledItem->program_activity_id,
                        'program_activity_item_id' => $cancelledItem->program_activity_item_id,
                        'settling_for' => $cancelledItem->id,
                        'payment_type' => RequestPaymentType::Offset,
                        'act_quantity' => 1,
                        'unit_quantity' => 'Lsum',
                        'act_amount_per_item' => -$useAmount,
                        'description' => '[Offset-Cancelled] '.$cancelledItem->description,
                        'self_account' => $cancelledItem->self_account,
                        'bank_name' => $cancelledItem->bank_name,
                        'bank_account' => $cancelledItem->bank_account,
                        'account_owner' => $cancelledItem->account_owner,
                        'status' => RequestItemStatus::WaitingApproval,
                    ]);

                    $offsetItems[] = $offsetItem;

                    // Update tracking
                    $cancelled[$index]['remaining'] = $amount - $useAmount;
                    $offsetRemaining -= $useAmount;
                }
            }

            // Allocate offsets from variance items (FIFO)
            foreach ($variance as $index => $varianceData) {
                if ($offsetRemaining <= 0) {
                    break;
                }

                $varianceItem = $varianceData['item'];
                $amount = $varianceData['variance'];

                $useAmount = min($amount, $offsetRemaining);

                if ($useAmount > 0) {
                    // Create offset request item
                    $offsetItem = new RequestItem([
                        'settlement_id' => $settlement->id,
                        'coa_id' => $coaId,
                        'program_activity_id' => $varianceItem->program_activity_id,
                        'program_activity_item_id' => $varianceItem->program_activity_item_id,
                        'settling_for' => $varianceItem->id,
                        'payment_type' => RequestPaymentType::Offset,
                        'act_quantity' => 1,
                        'unit_quantity' => 'Lsum',
                        'act_amount_per_item' => -$useAmount,
                        'description' => '[Offset-Selisih] '.$varianceItem->description,
                        'self_account' => $varianceItem->self_account,
                        'bank_name' => $varianceItem->bank_name,
                        'bank_account' => $varianceItem->bank_account,
                        'account_owner' => $varianceItem->account_owner,
                        'status' => RequestItemStatus::WaitingApproval,
                    ]);

                    $offsetItems[] = $offsetItem;

                    // Mark original item as Closed since variance is being used as offset
                    $varianceItem->status = RequestItemStatus::Closed;
                    $varianceItem->save();

                    // Update tracking
                    $variance[$index]['remaining'] = $amount - $useAmount;
                    $offsetRemaining -= $useAmount;
                }
            }

            // Calculate remaining funds for refund
            foreach ($cancelled as $cancelledData) {
                $remaining = $cancelledData['remaining'] ?? $cancelledData['amount'];
                if ($remaining > 0) {
                    $totalRefundAmount += $remaining;
                }
            }

            foreach ($variance as $varianceData) {
                $remaining = $varianceData['remaining'] ?? $varianceData['variance'];
                if ($remaining > 0) {
                    $totalRefundAmount += $remaining;

                    // Mark original item as Closed since remaining goes to settlement refund
                    $varianceItem = $varianceData['item'];
                    $varianceItem->status = RequestItemStatus::WaitingSettlementReview;
                    $varianceItem->save();
                }
            }
        }

        // Save all offset items
        foreach ($offsetItems as $offsetItem) {
            $offsetItem->save();
        }

        return [
            'offset_items' => $offsetItems,
            'total_refund_amount' => $totalRefundAmount,
        ];
    }

    /**
     * Create reimbursement items for overspent request items
     */
    public function createReimbursementItems(array $overspentResults, Settlement $settlement): array
    {
        $reimbursementItems = [];

        foreach ($overspentResults as $result) {
            $originalItem = $result['item'];
            $variance = $result['variance'];
            $attachment = $result['attachment'] ?? null;

            $reimbursementItem = new RequestItem([
                'settlement_id' => $settlement->id,
                'coa_id' => $originalItem->coa_id,
                'program_activity_id' => $originalItem->program_activity_id,
                'program_activity_item_id' => $originalItem->program_activity_item_id,
                'settling_for' => $originalItem->id,
                'payment_type' => RequestPaymentType::Reimburse,
                'act_quantity' => 1,
                'unit_quantity' => 'Lsum',
                'act_amount_per_item' => abs($variance),
                'description' => '[Auto-Selisih] '.$originalItem->description,
                'self_account' => $originalItem->self_account,
                'bank_name' => $originalItem->bank_name,
                'bank_account' => $originalItem->bank_account,
                'account_owner' => $originalItem->account_owner,
                'status' => RequestItemStatus::WaitingApproval,
            ]);

            $reimbursementItem->save();

            // Copy attachment if available
            if ($attachment) {
                $attachment->copy($reimbursementItem, 'request_item_settlement_attachments', 'local');
            }

            $reimbursementItems[] = $reimbursementItem;
        }

        return $reimbursementItems;
    }
}
