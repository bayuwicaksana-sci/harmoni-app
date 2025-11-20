<?php

namespace App\Mail;

use App\Enums\ApprovalAction;
use App\Enums\RequestPaymentType;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class DailyPaymentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public DailyPaymentRequest $request,
        public ?Employee $recipient = null
    ) {
        //
    }

    /**
     * Format currency for display
     */
    protected function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get formatted request items
     */
    protected function getFormattedItems(): array
    {
        return $this->request->requestItems->map(function ($item) {
            $data = [
                'coa' => $item->coa->name,
                'description' => $item->description,
                'paymentType' => $item->payment_type->getLabel(),
                'quantity' => $item->quantity,
                'unitQuantity' => $item->unit_quantity,
                'amountPerItem' => $this->formatCurrency($item->amount_per_item),
                'subtotal' => $this->formatCurrency($item->total_amount),
                'attachments' => $item->getMedia('request_item_attachments') ?? [],
                'taxMethod' => $item->tax_method ?? null,
                'taxType' => $item->tax_type ?? null,
                'taxAmount' => $item->tax_amount ? $this->formatCurrency($item->tax_amount) : null,
                'netAmount' => $item->net_amount ? $this->formatCurrency($item->net_amount) : null,
            ];

            // Add advance percentage if applicable
            // if ($item->payment_type === RequestPaymentType::Advance && $item->advance_percentage) {
            //     $data['advancePercentage'] = $item->advance_percentage;
            // }

            return $data;
        })->toArray();
    }

    /**
     * Get approval history
     */
    protected function getApprovalHistory(): array
    {
        return $this->request->approvalHistories
            ->sortBy('sequence')
            ->map(fn($history) => [
                'approver' => $history->approver->user->name . ' (' . $history->approver->jobTitle->title . ')',
                'action' => $history->action->getLabel(),
                'date' => $history->approved_at?->format('d M Y, H:i'),
                'notes' => $history->notes,
            ])->toArray();
    }
}
