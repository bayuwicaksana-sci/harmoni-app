<?php

namespace App\Services;

use App\Models\RequestItem;
use Exception;
use Illuminate\Support\Facades\Log;

class SettlementFormDataService
{
    /**
     * Parse money format from Indonesian format to float
     * Handles both raw string format (1.000.000,00) and already-parsed floats
     */
    private function parseMoney($value): float
    {
        // If already numeric, return as float
        if (is_numeric($value)) {
            return (float) $value;
        }

        // If string, parse Indonesian format
        if (is_string($value)) {
            return (float) str_replace(['.', ','], ['', '.'], $value);
        }

        // Default to 0
        return 0.0;
    }

    /**
     * Extract and validate form data from receipts repeater
     * Returns structured data ready for processing
     */
    public function extractFormData(array $formData): array
    {
        // dd($formData);
        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;
        $newRequestItemTotal = 0;

        $processedReceipts = [];

        // Process each receipt
        foreach ($formData['settlementReceipts'] ?? [] as $receiptIndex => $receipt) {
            // SettlementReceipt is already created by Filament via relationship
            // We just need to get the ID to link RequestItems
            $settlementReceiptId = $receipt['id'] ?? null;

            $processedReceipt = [
                'settlement_receipt_id' => $settlementReceiptId,
                'attachment' => $receipt['attachment'] ?? null,
                'realization_date' => $receipt['realization_date'] ?? null,
                'request_items' => [],
                'new_request_items' => [],
            ];

            // dd($receipt['requestItems']);

            // Process requestItems
            foreach ($receipt['requestItems'] ?? [] as $itemIndex => $item) {
                if (empty($item['id'])) {
                    continue;
                }

                if ((! isset($item['is_unplanned']) || $item['is_unplanned'] === null || $item['is_unplanned'] === false) && $item['id'] !== 'new') {
                    // Fetch the RequestItem from database to get accurate data
                    $requestItem = RequestItem::find($item['id']);

                    if (! $requestItem) {
                        Log::warning("RequestItem not found: {$item['id']}");

                        continue;
                    }

                    // Calculate request_total_price from database values
                    $requestTotalPrice = (float) $requestItem->quantity * (float) $requestItem->amount_per_item;

                    // Check if realized
                    $isRealized = $item['is_realized'] ?? true;

                    // Build processed item
                    $processedItem = [
                        'id' => $requestItem->id,
                        'quantity' => (float) $requestItem->quantity,
                        'unit_quantity' => $requestItem->unit_quantity,
                        'amount_per_item' => (float) $requestItem->amount_per_item,
                        'request_total_price' => $requestTotalPrice,
                        'is_realized' => $isRealized,
                    ];

                    // Add to approved amount
                    $approvedAmount += $requestTotalPrice;

                    if (! $isRealized) {
                        // If not realized, add to cancelled amount
                        $cancelledAmount += $requestTotalPrice;

                        // Set actual values to 0
                        $processedItem['act_quantity'] = 0;
                        $processedItem['act_amount_per_item'] = 0;
                        $processedItem['actual_total_price'] = 0;
                        $processedItem['variance'] = $requestTotalPrice;
                    } else {
                        // Calculate actual_total_price from user input
                        $actualQuantity = $item['act_quantity'] ?? 0;
                        $actualAmountPerItem = $this->parseMoney($item['act_amount_per_item'] ?? 0);
                        $actualTotalPrice = $actualQuantity * $actualAmountPerItem;

                        // Validate that actual values are provided
                        if ($actualQuantity <= 0 || $actualAmountPerItem <= 0) {
                            throw new Exception('Kuantitas aktual dan harga per item harus lebih besar dari 0 untuk item yang direalisasikan');
                        }

                        $processedItem['act_quantity'] = $actualQuantity;
                        $processedItem['act_amount_per_item'] = $actualAmountPerItem;
                        $processedItem['actual_total_price'] = $actualTotalPrice;

                        // Add to spent amount
                        $spentAmount += $actualTotalPrice;

                        // Calculate variance
                        $variance = $requestTotalPrice - $actualTotalPrice;
                        $processedItem['variance'] = $variance;
                    }

                    $processedReceipt['request_items'][] = $processedItem;
                } else {
                    // Skip empty new items - check if at least item description or COA exists
                    if (empty($item['description']) && empty($item['coa_id'])) {
                        continue;
                    }

                    // Calculate total_price
                    $qty = $item['act_quantity'] ?? 0;
                    $basePrice = is_float($item['act_amount_per_item']) ? $item['act_amount_per_item'] : $this->parseMoney($item['act_amount_per_item'] ?? 0);
                    $totalPrice = $qty * $basePrice;

                    $processedItem = [
                        'coa_id' => $item['coa_id'] ?? null,
                        'program_activity_id' => $item['program_activity_id'] ?? null,
                        'description' => $item['description'] ?? null,
                        'act_quantity' => $qty,
                        'unit_quantity' => $item['unit_quantity'] ?? null,
                        'act_amount_per_item' => $basePrice,
                        'total_price' => $totalPrice,
                    ];

                    if (isset($item['id']) && ! empty($item['id'])) {
                        $processedItem['id'] = $item['id'];
                    }

                    if (isset($item['item_image']) && ! empty($item['item_image'])) {
                        $processedItem['item_image'] = $item['item_image'];
                    }

                    // Add to new_request_item_total
                    $newRequestItemTotal += $totalPrice;

                    // new_request_items only affect spent_amount
                    $spentAmount += $totalPrice;

                    $processedReceipt['new_request_items'][] = $processedItem;
                }

            }

            // Validate: At least one request item is required per receipt
            if (empty($processedReceipt['request_items'])) {
                throw new Exception('Setiap bukti kwitansi harus memiliki minimal 1 Item Request dari Payment Request yang sudah disetujui');
            }

            $processedReceipts[] = $processedReceipt;
        }

        // Calculate final variance
        $variance = $approvedAmount - $spentAmount;

        return [
            'receipts' => $processedReceipts,
            'financial_summary' => [
                'approved_request_amount' => $approvedAmount,
                'cancelled_amount' => $cancelledAmount,
                'spent_amount' => $spentAmount,
                'new_request_item_total' => $newRequestItemTotal,
                'variance' => $variance,
            ],
        ];
    }
}
