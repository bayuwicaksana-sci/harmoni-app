<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SettlementConfirmationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Settlement $settlement,
        public Employee $financeOperator
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Settlement Menunggu Konfirmasi - '.$this->settlement->settlement_number,
        );
    }

    public function content(): Content
    {
        $hasRefund = $this->settlement->getMedia('refund_receipts')->isNotEmpty();
        $message = $hasRefund
            ? 'Bukti pengembalian dana telah diupload. Mohon verifikasi dan konfirmasi.'
            : 'Settlement tidak memerlukan pengembalian dana. Mohon verifikasi dan konfirmasi.';

        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => 'Konfirmasi Settlement Diperlukan',
                'recipientName' => $this->financeOperator->user->name,
                'msg' => $message,
                'requestNumber' => $this->settlement->settlement_number,
                'status' => $this->settlement->status->getLabel(),
                'requesterName' => $this->settlement->submitter->user->name,
                'actionButton' => [
                    'text' => 'Lihat & Konfirmasi Settlement',
                    'url' => route('filament.admin.resources.settlements.view', $this->settlement),
                ],
            ],
        );
    }
}
