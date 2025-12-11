<?php

namespace App\Services;

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettlementItemProcessingService
{
    /**
     * Process all receipts from extracted form data
     * Links items to existing SettlementReceipts (created by Filament via relationship)
     * Returns array of results (each with 'type', 'item', and additional data)
     */
    public function processReceipts(array $receipts, Settlement $settlement, bool $isEdit = false): array
    {
        // If edit mode: delete existing offset/reimburse items for recalculation
        if ($isEdit) {
            $settlement->settlementItems()
                ->whereIn('payment_type', [
                    RequestPaymentType::Offset,
                    RequestPaymentType::Reimburse,
                ])
                ->delete();
        }

        $results = [];

        // dd($receipts);

        foreach ($receipts as $receiptIndex => $receipt) {
            // Find existing SettlementReceipt created by Filament via relationship
            $settlementReceiptId = $receipt['settlement_receipt_id'] ?? null;

            if (! $settlementReceiptId) {
                throw new Exception('Settlement receipt ID not found in form data');
            }

            $settlementReceipt = SettlementReceipt::find($settlementReceiptId);

            if (! $settlementReceipt) {
                throw new Exception("SettlementReceipt not found: {$settlementReceiptId}");
            }

            // Process request_items (existing items)
            foreach ($receipt['request_items'] ?? [] as $requestItem) {
                $result = $this->processRequestItem($requestItem, $receipt, $settlement, $settlementReceipt);

                $results[] = $result;
            }

            // Process new_request_items (unplanned items)
            foreach ($receipt['new_request_items'] ?? [] as $newRequestItem) {
                // Skip empty new items - check if at least item description exists
                if (empty($newRequestItem['description']) && empty($newRequestItem['coa_id'])) {
                    continue;
                }

                $result = $this->processNewItem($newRequestItem, $receipt, $settlement, $settlementReceipt);

                $results[] = $result;
            }
        }

        // Save all processed items
        foreach ($results as $result) {
            $result['item']->save();
        }

        return $results;
    }

    /**
     * Process a single request item from form data
     */
    private function processRequestItem(array $itemData, array $receipt, Settlement $settlement, SettlementReceipt $settlementReceipt): array
    {
        if (empty($itemData['id'])) {
            throw new Exception('ID Item Request diperlukan');
        }

        $originalRequestItem = RequestItem::find($itemData['id']);

        if (! $originalRequestItem) {
            throw new Exception('Item Request tidak ditemukan');
        }

        // Fix #9 (Approach B): Check if item is linked to a rejected DPR
        // If so, create a duplicate for the new DPR to preserve audit trail
        if ($originalRequestItem->daily_payment_request_id !== null) {
            $existingDPR = $originalRequestItem->dailyPaymentRequest;
            if ($existingDPR && $existingDPR->status === \App\Enums\DPRStatus::Rejected) {
                // Create a duplicate for the new DPR
                $originalRequestItem = $originalRequestItem->replicate();
                $originalRequestItem->daily_payment_request_id = null; // Will be set by new DPR
            }
        }

        // Link to settlement and settlement receipt
        $originalRequestItem->settlement_id = $settlement->id;
        $originalRequestItem->settlement_receipt_id = $settlementReceipt->id;

        // Fix #8: Reset status if item was in WaitingSettlementReview from previous rejection
        if ($originalRequestItem->status === RequestItemStatus::WaitingSettlementReview) {
            $originalRequestItem->status = RequestItemStatus::WaitingSettlement;
        }

        $requestTotalPrice = $originalRequestItem->quantity * $originalRequestItem->amount_per_item;

        if ($itemData['is_realized']) {
            // Set actual values
            $originalRequestItem->act_quantity = $itemData['act_quantity'];
            $originalRequestItem->act_amount_per_item = $itemData['act_amount_per_item'];

            $variance = $itemData['variance'];

            if ($variance < 0) {
                // Overspent
                $originalRequestItem->status = RequestItemStatus::WaitingSettlementReview;

                return [
                    'type' => 'overspent',
                    'item' => $originalRequestItem,
                    'variance' => $variance,
                ];
            } elseif ($variance > 0) {
                // Underspent
                $originalRequestItem->status = RequestItemStatus::WaitingRefund;

                return [
                    'type' => 'variance',
                    'item' => $originalRequestItem,
                    'variance' => $variance,
                ];
            } else {
                // Exact match
                $originalRequestItem->status = RequestItemStatus::WaitingSettlementReview;

                return [
                    'type' => 'exact',
                    'item' => $originalRequestItem,
                ];
            }
        } else {
            // Not realized - cancelled
            $originalRequestItem->act_quantity = 0;
            $originalRequestItem->act_amount_per_item = 0;
            $originalRequestItem->status = RequestItemStatus::Cancelled;

            return [
                'type' => 'cancelled',
                'item' => $originalRequestItem,
                'amount' => $requestTotalPrice,
            ];
        }
    }

    /**
     * Process a single new unplanned item from form data
     */
    private function processNewItem(array $itemData, array $receipt, Settlement $settlement, SettlementReceipt $settlementReceipt): array
    {
        // dd($itemData);
        if (empty($itemData['coa_id'])) {
            throw new Exception('COA diperlukan untuk item baru');
        }

        $totalPrice = $itemData['act_quantity'] * $itemData['act_amount_per_item'];

        // Find program activity item ID if program activity is set
        $programActivityItemId = null;
        if (! empty($itemData['program_activity_id']) && ! empty($itemData['description'])) {
            $programActivityItemId = ProgramActivityItem::whereProgramActivityId($itemData['program_activity_id'])
                ->whereDescription($itemData['description'])
                ->value('id');
        }

        // dd($itemData);

        // Check if this is an existing unplanned item or a new one
        if ((isset($itemData['unplanned_item_id']) && ! empty($itemData['unplanned_item_id'])) || (isset($itemData['id']) && ! empty($itemData['id']))) {
            // Update existing unplanned item
            $newItem = RequestItem::find($itemData['id']) ?? RequestItem::find($itemData['unplanned_item_id']);

            // if (! $newItem || $newItem->settlement_id !== $settlement->id) {
            //     throw new Exception('Invalid unplanned item ID');
            // }

            Log::info($newItem);

            // Update the existing item
            $newItem->update([
                'settlement_id' => $settlement->id,
                'settlement_receipt_id' => $settlementReceipt->id,
                'coa_id' => $itemData['coa_id'],
                'program_activity_id' => $itemData['program_activity_id'] ?? null,
                'program_activity_item_id' => $programActivityItemId,
                'act_quantity' => $itemData['act_quantity'],
                'unit_quantity' => $itemData['unit_quantity'],
                'act_amount_per_item' => $itemData['act_amount_per_item'],
                'description' => '[New Item] '.$itemData['description'],
                'status' => RequestItemStatus::WaitingApproval,
            ]);

            if (isset($itemData['item_image']) && ! empty($itemData['item_image'])) {
                foreach ($itemData['item_image'] as $fileIndex => $file) {
                    $newItem->addMedia($file)->toMediaCollection('request_item_image', 'local');
                }
            }
        } else {
            // Create new unplanned item
            $newItem = new RequestItem([
                'settlement_id' => $settlement->id,
                'settlement_receipt_id' => $settlementReceipt->id,
                'coa_id' => $itemData['coa_id'],
                'program_activity_id' => $itemData['program_activity_id'] ?? null,
                'program_activity_item_id' => $programActivityItemId,
                'payment_type' => RequestPaymentType::Reimburse,
                'act_quantity' => $itemData['act_quantity'],
                'unit_quantity' => $itemData['unit_quantity'],
                'act_amount_per_item' => $itemData['act_amount_per_item'],
                'description' => '[New Item] '.$itemData['description'],
                'self_account' => true,
                'bank_name' => Auth::user()->employee->bank_name,
                'bank_account' => Auth::user()->employee->bank_account_number,
                'account_owner' => Auth::user()->employee->bank_cust_name,
                'status' => RequestItemStatus::WaitingApproval,
                'is_unplanned' => true,
            ]);

            if (isset($itemData['item_image']) && ! empty($itemData['item_image'])) {
                foreach ($itemData['item_image'] as $fileIndex => $file) {
                    $newItem->addMedia($file)->toMediaCollection('request_item_image', 'local');
                }
            }
        }

        return [
            'type' => 'new',
            'item' => $newItem,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Categorize processed items by processing needs
     * Returns arrays grouped by COA and type
     */
    public function categorizeItems(Collection $results): array
    {
        $categorized = [
            'cancelled' => [],
            'variance' => [],
            'overspent' => [],
            'new' => [],
            'exact' => [],
        ];

        foreach ($results as $result) {
            $type = $result['type'] ?? 'unknown';

            if (isset($categorized[$type])) {
                $coaId = $result['item']->coa_id;

                if (! isset($categorized[$type][$coaId])) {
                    $categorized[$type][$coaId] = [];
                }

                $categorized[$type][$coaId][] = $result;
            }
        }

        // Sort cancelled and variance items by ID (FIFO) within each COA
        foreach ($categorized['cancelled'] as $coaId => &$items) {
            usort($items, fn ($a, $b) => $a['item']->id <=> $b['item']->id);
        }

        foreach ($categorized['variance'] as $coaId => &$items) {
            usort($items, fn ($a, $b) => $a['item']->id <=> $b['item']->id);
        }

        return $categorized;
    }
}
