<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use App\Enums\ApprovalAction;
use App\Models\DailyPaymentRequest;
use App\Models\Employee;

/**
 * Email notification when an approval step is completed (not final)
 */
class ApprovalStepCompletedMail extends DailyPaymentRequestMail
{
    public function __construct(
        DailyPaymentRequest $request,
        public Employee $completedApprover,
        public ApprovalAction $action, // 'approved' or 'rejected'
        public ?Employee $nextApprover = null
    ) {
        parent::__construct($request, $request->requester);
    }

    public function envelope(): Envelope
    {
        $actionText = $this->action === ApprovalAction::Approved ? 'Approved by' : 'Rejected by';
        return new Envelope(
            subject: $actionText . ' ' . $this->completedApprover->user->name . ' - ' . $this->request->request_number,
        );
    }

    public function content(): Content
    {
        $isApproved = $this->action === ApprovalAction::Approved;
        $currentSequence = $this->request->approvalHistories()
            ->where('action', '!=', ApprovalAction::Pending)
            ->count();
        $totalSteps = $this->request->approvalHistories()->count();

        return new Content(
            view: 'emails.dpr-email-template',
            with: [
                'notificationType' => $isApproved ? 'Update Approval' : 'Permintaan Ditolak',
                'recipientName' => $this->request->requester->user->name,
                'msg' => $this->completedApprover->user->name . ' (' . $this->completedApprover->jobTitle->title . ') telah ' .
                    ($isApproved ? 'menyetujui' : 'menolak') . ' permintaan Anda.',

                'requestNumber' => $this->request->request_number,
                'status' => $this->request->status->getLabel(),
                'requesterName' => $this->request->requester->user->name,
                'totalAmount' => $this->formatCurrency($this->request->total_request_amount),

                'approvalInfo' => [
                    'title' => $isApproved ? 'Progress Approval' : 'Alasan Penolakan',
                    'msg' => $isApproved
                        ? 'Permintaan Anda telah disetujui pada step ' . $currentSequence . ' dari ' . $totalSteps . '.'
                        : 'Berikut adalah detail penolakan:',
                    'currentStep' => 'Step ' . $currentSequence . ' of ' . $totalSteps,
                    'nextApprover' => $isApproved && $this->nextApprover
                        ? $this->nextApprover->user->name . ' (' . $this->nextApprover->jobTitle->title . ')'
                        : null,
                    'history' => $this->getApprovalHistory(),
                ],

                'actionButton' => [
                    'text' => 'Lihat Detail',
                    'url' => route('filament.admin.resources.daily-payment-requests.view', $this->request),
                    'color' => $isApproved ? '#6366f1' : '#dc2626'
                ],

                'additionalNotes' => $isApproved
                    ? 'Permintaan Anda sedang dalam proses approval. Anda akan menerima notifikasi ketika ada update lebih lanjut.'
                    : 'Hubungi ' . $this->completedApprover->user->name . ' jika Anda memerlukan klarifikasi lebih lanjut.',
            ],
        );
    }
}
