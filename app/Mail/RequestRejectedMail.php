<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use App\Enums\ApprovalAction;
use App\Enums\DPRStatus;
use App\Models\DailyPaymentRequest;

/**
 * Email notification when request is rejected
 */
class RequestRejectedMail extends DailyPaymentRequestMail
{
    public function __construct(
        DailyPaymentRequest $request,
        public string $rejectionReason
    ) {
        parent::__construct($request, $request->requester);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Request Rejected - ' . $this->request->request_number,
        );
    }

    public function content(): Content
    {
        $rejectedHistory = $this->request->approvalHistories()
            ->where('action', ApprovalAction::Rejected)
            ->get()
            ->map(fn($history) => [
                'approver' => $history->approver->user->name . ' (' . $history->approver->jobTitle->title . ')',
                'action' => ApprovalAction::Rejected->getLabel(),
                'date' => $history->approved_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                'notes' => $history->notes,
            ])->toArray();

        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => 'Permintaan Ditolak',
                'recipientName' => $this->request->requester->user->name,
                'msg' => 'Permintaan pembayaran Anda telah ditolak. Silakan periksa catatan dari approver untuk informasi lebih lanjut.',

                'requestNumber' => $this->request->request_number,
                'status' => DPRStatus::Rejected->getLabel(),
                'requesterName' => $this->request->requester->user->name,
                'requesterDepartment' => $this->request->requester->jobTitle->department->name,
                'requestDate' => $this->request->created_at->translatedFormat('d F Y'),
                'totalAmount' => $this->formatCurrency($this->request->total_request_amount),
                'itemCount' => $this->request->requestItems->count(),

                'approvalInfo' => [
                    'title' => 'Alasan Penolakan',
                    'msg' => 'Berikut adalah catatan dari approver yang menolak permintaan Anda:',
                    'history' => $rejectedHistory,
                ],

                'actionButton' => [
                    'text' => 'Lihat Detail',
                    'url' => route('filament.admin.resources.daily-payment-requests.view', $this->request),
                    'color' => '#dc2626'
                ],

                // 'additionalNotes' => 'Anda dapat mengajukan permintaan baru dengan memperbaiki hal-hal yang disebutkan dalam catatan penolakan. Hubungi approver jika memerlukan klarifikasi lebih lanjut.',
            ],
        );
    }
}
