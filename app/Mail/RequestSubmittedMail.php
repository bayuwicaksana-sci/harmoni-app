<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use App\Enums\ApprovalAction;

/**
 * Email notification when a new request is submitted
 */
class RequestSubmittedMail extends DailyPaymentRequestMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Request Submitted Successfully - ' . $this->request->request_number,
        );
    }

    public function content(): Content
    {
        // Get next approver info
        $nextApproval = $this->request->approvalHistories()
            ->where('action', ApprovalAction::Pending)
            ->orderBy('sequence')
            ->first();

        $totalSteps = $this->request->approvalHistories()->count();

        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => 'Konfirmasi Pengajuan',
                'recipientName' => $this->request->requester->user->name,
                'msg' => 'Permintaan pembayaran Anda telah berhasil diajukan dan sedang menunggu approval.',
                'requestNumber' => $this->request->request_number,
                'status' => $this->request->status->getLabel(),
                'requesterName' => $this->request->requester->user->name,
                'requesterDepartment' => $this->request->requester->jobTitle->department->name,
                'requestDate' => $this->request->created_at->translatedFormat('d F Y'),
                'totalAmount' => $this->formatCurrency($this->request->total_request_amount),
                'itemCount' => $this->request->requestItems->count(),

                'requestItems' => $this->getFormattedItems(),

                'approvalInfo' => [
                    'title' => 'Status Approval',
                    'msg' => 'Permintaan Anda sedang menunggu approval dari ' . $nextApproval?->approver->user->name,
                    'currentStep' => 'Step 1 of ' . $totalSteps,
                    'nextApprover' => $nextApproval ? $nextApproval->approver->user->name . ' (' . $nextApproval->approver->jobTitle->title . ')' : '-',
                ],

                'actionButton' => [
                    'text' => 'Lihat Detail Permintaan',
                    'url' => route('filament.admin.resources.daily-payment-requests.view', $this->request),
                    'color' => '#6366f1'
                ],

                'additionalNotes' => 'Anda dapat memantau status permintaan melalui dashboard.',
            ],
        );
    }
}
