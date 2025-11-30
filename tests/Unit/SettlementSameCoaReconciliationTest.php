<?php

declare(strict_types=1);

use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Models\Coa;
use App\Models\Employee;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use App\Services\SettlementDPRService;
use App\Services\SettlementItemProcessingService;
use App\Services\SettlementOffsetCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('same coa variance offsets overspent without creating dpr items', function () {
    // Create test data
    $employee = Employee::factory()->create();
    $coa = Coa::factory()->create();
    $settlement = Settlement::factory()->create([
        'submitter_id' => $employee->id,
        'status' => SettlementStatus::Draft,
    ]);

    // Create settlement receipt
    $settlementReceipt = SettlementReceipt::factory()->create([
        'settlement_id' => $settlement->id,
    ]);

    // Create original request items
    $itemA = RequestItem::factory()->create([
        'settlement_id' => $settlement->id,
        'settlement_receipt_id' => $settlementReceipt->id,
        'coa_id' => $coa->id,
        'description' => 'Item A',
        'quantity' => 1,
        'amount_per_item' => 2000000, // Requested 2M
        'total_amount' => 2000000,
        'act_quantity' => 1,
        'act_amount_per_item' => 1500000, // Actual 1.5M
        'total_act_amount' => 1500000,
        'status' => RequestItemStatus::WaitingRefund, // 500K variance (positive)
    ]);

    $itemB = RequestItem::factory()->create([
        'settlement_id' => $settlement->id,
        'settlement_receipt_id' => $settlementReceipt->id,
        'coa_id' => $coa->id, // Same COA
        'description' => 'Item B',
        'quantity' => 1,
        'amount_per_item' => 1500000, // Requested 1.5M
        'total_amount' => 1500000,
        'act_quantity' => 1,
        'act_amount_per_item' => 2000000, // Actual 2M
        'total_act_amount' => 2000000,
        'status' => RequestItemStatus::WaitingSettlementReview, // -500K variance (negative)
    ]);

    // Process settlement
    $itemProcessor = new SettlementItemProcessingService;
    $offsetService = new SettlementOffsetCalculationService;
    $dprService = new SettlementDPRService;

    // Create results array mimicking SettlementItemProcessingService output
    $results = [
        [
            'type' => 'variance',
            'item' => $itemA,
            'variance' => 500000, // Positive variance
        ],
        [
            'type' => 'overspent',
            'item' => $itemB,
            'variance' => -500000, // Negative variance
        ],
    ];

    $categorized = $itemProcessor->categorizeItems(collect($results));

    // Process using our new method
    $processResult = $offsetService->processSettlement($categorized, $categorized['overspent'], $settlement);

    // Assertions
    expect($processResult['reimbursement_items'])->toHaveCount(0); // No reimbursement items created
    expect($processResult['offset_items'])->toHaveCount(0); // No offset items created
    expect($processResult['total_refund_amount'])->toBe(0); // No refund needed

    // Check DPR requirement
    $requiresDPR = $dprService->requiresDPRFromResults([
        'categorized' => $categorized,
        'reimbursement_items' => $processResult['reimbursement_items'],
        'offset_items' => $processResult['offset_items'],
    ]);

    expect($requiresDPR)->toBeFalse(); // DPR should not be required

    // Check that variance item was marked as closed
    $itemA->refresh();
    expect($itemA->status)->toBe(RequestItemStatus::Closed);

    // Check that overspent item remained in original status
    $itemB->refresh();
    expect($itemB->status)->toBe(RequestItemStatus::WaitingSettlementReview);
});
