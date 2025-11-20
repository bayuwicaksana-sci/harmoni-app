<?php

namespace App\Mail;

use App\Enums\DPRStatus;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\URL;

/**
 * Email notification for approvers when request needs approval
 */
class ApprovalRequestMail extends DailyPaymentRequestMail
{
    public function __construct(
        DailyPaymentRequest $request,
        public Employee $approver,
        public int $currentSequence,
        public int $totalSteps,
        public string $approvalPageUrl,
    ) {
        parent::__construct($request, $approver);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Approval Required - ' . $this->request->request_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => $this->approver->jobTitle->code === 'FO' ? 'Permintaan Review' : 'Permintaan Approval',
                'recipientName' => $this->approver->user->name,
                'msg' => 'Anda memiliki Daily Payment Request baru sebesar ' . $this->formatCurrency($this->request->total_request_amount) . ($this->approver->jobTitle->code === 'FO' ? ' yang memerlukan review Anda.' : ' yang memerlukan persetujuan Anda.'),

                'requestNumber' => $this->request->request_number,
                'status' => DPRStatus::Pending->getLabel(),
                'requesterName' => $this->request->requester->user->name,
                'requesterDepartment' => $this->request->requester->jobTitle->department->name,
                'requestDate' => $this->request->created_at->translatedFormat('d F Y'),
                'totalAmount' => $this->formatCurrency($this->request->total_request_amount),
                'itemCount' => $this->request->requestItems->count(),

                'requestItems' => $this->getFormattedItems(),

                'approvalInfo' => [
                    'title' => $this->approver->jobTitle->code === 'FO' ? 'Menunggu Review Anda' : 'Menunggu Approval Anda',
                    'msg' => 'Sebagai ' . $this->approver->jobTitle->title . ', Anda diminta untuk meninjau dan menyetujui permintaan ini.',
                    'currentStep' => 'Step ' . $this->currentSequence . ' of ' . $this->totalSteps,
                ],

                'actionButtons' => [
                    [
                        'text' => $this->approver->jobTitle->code === 'FO' ? 'Lihat Detail Request' : 'Review & Approve/Reject',
                        'url' => $this->approvalPageUrl,
                        'color' => '#6366f1'
                    ],
                ],

                'additionalNotes' => 'Harap tinjau dan berikan keputusan pada permintaan ini.',
            ],
        );
    }
}
