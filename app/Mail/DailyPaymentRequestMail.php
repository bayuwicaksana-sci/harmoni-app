<?php

namespace App\Mail;

use App\Enums\RequestPaymentType;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        return 'Rp '.number_format($amount, 0, ',', '.');
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
                'quantity' => $item->payment_type === RequestPaymentType::Advance ? $item->quantity : $item->act_quantity,
                'unitQuantity' => $item->unit_quantity,
                'amountPerItem' => $item->payment_type === RequestPaymentType::Advance ? $this->formatCurrency($item->amount_per_item) : $this->formatCurrency($item->act_amount_per_item),
                'subtotal' => $item->payment_type === RequestPaymentType::Advance ? $this->formatCurrency($item->total_amount) : $this->formatCurrency($item->total_act_amount),
                'attachments' => $item->getMedia('request_item_attachments') ?? [],
                'taxMethod' => $item->tax_method ? $item->tax_method->getLabel() : null,
                'taxType' => $item->tax ? $item->tax->name : null,
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
            ->map(fn ($history) => [
                'approver' => $history->approver->user->name.' ('.$history->approver->jobTitle->title.')',
                'action' => $history->action->getLabel(),
                'date' => $history->approved_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                'notes' => $history->notes,
            ])->toArray();
    }
}
