<?php

namespace App\Mail;

use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SettlementRevisionRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Settlement $settlement) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Settlement Memerlukan Revisi - '.$this->settlement->settlement_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => 'Revisi Diperlukan',
                'recipientName' => $this->settlement->submitter->user->name,
                'msg' => 'Finance Operator meminta revisi pada settlement Anda.',
                'requestNumber' => $this->settlement->settlement_number,
                'additionalNotes' => 'Alasan: '.$this->settlement->revision_notes,
                'actionButton' => [
                    'text' => 'Lihat & Edit Settlement',
                    'url' => route('filament.admin.resources.settlements.edit', $this->settlement),
                ],
            ],
        );
    }
}
