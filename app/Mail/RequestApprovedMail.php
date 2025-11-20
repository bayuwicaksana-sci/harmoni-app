<?php

namespace App\Mail;

use App\Enums\DPRStatus;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

/**
 * Email notification when request is approved
 */
class RequestApprovedMail extends DailyPaymentRequestMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Request Approved - ' . $this->request->request_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => 'Permintaan Disetujui ✓',
                'recipientName' => $this->request->requester->user->name,
                'msg' => 'Selamat! Permintaan pembayaran Anda telah disetujui oleh semua approver dan siap untuk diproses.',

                'requestNumber' => $this->request->request_number,
                'status' => DPRStatus::Approved->getLabel(),
                'requesterName' => $this->request->requester->user->name,
                'requesterDepartment' => $this->request->requester->jobTitle->department->name,
                'requestDate' => $this->request->created_at->translatedFormat('d F Y'),
                'totalAmount' => $this->formatCurrency($this->request->total_request_amount),
                'itemCount' => $this->request->requestItems->count(),

                'approvalInfo' => [
                    'title' => 'Riwayat Approval',
                    'msg' => 'Semua approver telah menyetujui permintaan Anda.',
                    'history' => $this->getApprovalHistory(),
                ],

                'actionButton' => [
                    'text' => 'Lihat Detail Permintaan',
                    'url' => route('filament.admin.resources.daily-payment-requests.view', $this->request),
                    'color' => '#059669'
                ],

                'additionalNotes' => 'Pembayaran akan diproses oleh tim Finance dalam 2-3 hari kerja. Anda akan menerima notifikasi ketika pembayaran telah selesai.',
            ],
        );
    }
}
