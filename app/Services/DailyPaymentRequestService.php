<?php
// app/Services/PaymentRequestService.php

namespace App\Services;

use App\Models\DailyPaymentRequest;
use App\Models\PaymentRequestAudit;
use App\Services\SequenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\DailyPaymentRequestException;
use App\Events\PaymentRequestSubmitted;

class DailyPaymentRequestService
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator
    ) {}

    /**
     * Create a new draft payment request
     */
    public function createDraft(array $data): DailyPaymentRequest
    {
        return DB::transaction(function () use ($data) {
            $paymentRequest = DailyPaymentRequest::create([
                'status' => 'draft',
                'user_id' => Auth::id(),
                'vendor_name' => $data['vendor_name'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'items' => $data['items'] ?? [],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->createAudit($paymentRequest, 'created', null, $paymentRequest->toArray());

            return $paymentRequest;
        });
    }

    /**
     * Update a draft payment request
     */
    public function updateDraft(DailyPaymentRequest $paymentRequest, array $data): DailyPaymentRequest
    {
        if (!$paymentRequest->can_edit) {
            throw new DailyPaymentRequestException('Only draft requests can be edited');
        }

        return DB::transaction(function () use ($paymentRequest, $data) {
            $oldValues = $paymentRequest->toArray();

            $paymentRequest->update([
                'vendor_name' => $data['vendor_name'] ?? $paymentRequest->vendor_name,
                'amount' => $data['amount'] ?? $paymentRequest->amount,
                'description' => $data['description'] ?? $paymentRequest->description,
                'items' => $data['items'] ?? $paymentRequest->items,
                'notes' => $data['notes'] ?? $paymentRequest->notes,
            ]);

            $this->createAudit($paymentRequest, 'updated', $oldValues, $paymentRequest->toArray());

            return $paymentRequest->fresh();
        });
    }

    /**
     * Submit a draft payment request
     */
    public function submitRequest(DailyPaymentRequest $paymentRequest): DailyPaymentRequest
    {
        if (!$paymentRequest->can_submit) {
            throw new DailyPaymentRequestException('This request cannot be submitted');
        }

        return DB::transaction(function () use ($paymentRequest) {
            $oldValues = $paymentRequest->toArray();

            // Generate the sequential ID
            $requestId = $this->sequenceGenerator->generateNextId('PAYMENT_REQUEST');

            // Update the payment request
            $paymentRequest->update([
                'request_id' => $requestId,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $this->createAudit(
                $paymentRequest,
                'submitted',
                $oldValues,
                $paymentRequest->toArray(),
                "Request submitted with ID: {$requestId}"
            );

            // Fire event for notifications, etc.
            event(new PaymentRequestSubmitted($paymentRequest));

            return $paymentRequest->fresh();
        });
    }

    /**
     * Approve a payment request
     */
    public function approveRequest(DailyPaymentRequest $paymentRequest, string $notes = null): DailyPaymentRequest
    {
        if ($paymentRequest->status !== 'submitted') {
            throw new DailyPaymentRequestException('Only submitted requests can be approved');
        }

        return DB::transaction(function () use ($paymentRequest, $notes) {
            $oldValues = $paymentRequest->toArray();

            $paymentRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            $this->createAudit(
                $paymentRequest,
                'approved',
                $oldValues,
                $paymentRequest->toArray(),
                $notes
            );

            return $paymentRequest->fresh();
        });
    }

    /**
     * Reject a payment request
     */
    public function rejectRequest(DailyPaymentRequest $paymentRequest, string $reason): DailyPaymentRequest
    {
        if ($paymentRequest->status !== 'submitted') {
            throw new DailyPaymentRequestException('Only submitted requests can be rejected');
        }

        return DB::transaction(function () use ($paymentRequest, $reason) {
            $oldValues = $paymentRequest->toArray();

            $paymentRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $this->createAudit(
                $paymentRequest,
                'rejected',
                $oldValues,
                $paymentRequest->toArray(),
                $reason
            );

            return $paymentRequest->fresh();
        });
    }

    /**
     * Delete a draft payment request
     */
    public function deleteDraft(DailyPaymentRequest $paymentRequest): bool
    {
        if ($paymentRequest->status !== 'draft') {
            throw new DailyPaymentRequestException('Only draft requests can be deleted');
        }

        return DB::transaction(function () use ($paymentRequest) {
            $this->createAudit($paymentRequest, 'deleted', $paymentRequest->toArray(), null);

            return $paymentRequest->delete();
        });
    }

    /**
     * Create audit log entry
     */
    protected function createAudit(
        DailyPaymentRequest $paymentRequest,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        string $notes = null
    ): void {
        PaymentRequestAudit::create([
            'payment_request_id' => $paymentRequest->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => Auth::id(),
            'notes' => $notes,
        ]);
    }
}
