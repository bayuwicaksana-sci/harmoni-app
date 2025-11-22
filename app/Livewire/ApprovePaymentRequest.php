<?php

namespace App\Livewire;

use Exception;
use App\Enums\ApprovalAction;
use App\Enums\DPRStatus;
use App\Models\ApprovalHistory;
use App\Models\DailyPaymentRequest;
use App\Services\ApprovalService;
use App\Services\DPRNotificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class ApprovePaymentRequest extends Component
{
    use WithPagination, WithoutUrlPagination;

    public DailyPaymentRequest $request;
    public ApprovalHistory $approvalHistory;
    public string $notes = '';
    public bool $showApproveConfirm = false;
    public bool $showRejectConfirm = false;
    public bool $actionCompleted = false;
    public string $actionResult = ''; // 'approved' or 'rejected'
    public ?string $errorMessage = null;

    // #[Layout('components.layouts.approval')]
    public function render()
    {
        return view('livewire.approve-payment-request', [
            'request_items' => $this->request->requestItems()->paginate(1)
        ])->layout('components.layouts.approval');
    }

    public function mount($requestId, $approvalHistoryId)
    {
        // Load the request with all relationships
        $this->request = DailyPaymentRequest::with([
            'requester.jobTitle.department',
            'requester.jobTitle',
            'requestItems',
            'approvalHistories.approver.jobTitle'
        ])->findOrFail($requestId);

        // Load the specific approval history
        $this->approvalHistory = ApprovalHistory::with('approver.jobTitle')
            ->findOrFail($approvalHistoryId);

        // Validate that this approval history belongs to this request
        if ($this->approvalHistory->daily_payment_request_id !== $this->request->id) {
            abort(403, 'Invalid approval link.');
        }

        // Check if already actioned
        if ($this->approvalHistory->action !== ApprovalAction::Pending) {
            $this->actionCompleted = true;
            $this->actionResult = $this->approvalHistory->action->value;
        }

        // Check if this is still the current pending approval (sequential check)
        if (!$this->actionCompleted && !$this->isCurrentPendingApproval()) {
            abort(403, 'This approval is not yet available. Please wait for previous approvals to complete.');
        }
    }

    /**
     * Check if this approval is the current pending one in the sequence
     */
    protected function isCurrentPendingApproval(): bool
    {
        $minPendingSequence = $this->request->approvalHistories()
            ->where('action', ApprovalAction::Pending)
            ->min('sequence');

        return $this->approvalHistory->sequence === $minPendingSequence;
    }

    /**
     * Show approve confirmation modal
     */
    public function confirmApprove()
    {
        $this->showApproveConfirm = true;
        $this->showRejectConfirm = false;
    }

    /**
     * Show reject confirmation modal
     */
    public function confirmReject()
    {
        $this->validate([
            'notes' => 'required|string|min:10|max:500',
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi.',
            'notes.min' => 'Alasan penolakan minimal 10 karakter.',
            'notes.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $this->showRejectConfirm = true;
        $this->showApproveConfirm = false;
    }

    /**
     * Cancel confirmation
     */
    public function cancelConfirm()
    {
        $this->showApproveConfirm = false;
        $this->showRejectConfirm = false;
    }

    /**
     * Approve the request
     */
    public function approve()
    {
        $this->errorMessage = null;

        try {
            // DB::beginTransaction();

            // Validate approval is still pending
            $this->approvalHistory->refresh();

            if ($this->approvalHistory->action !== ApprovalAction::Pending) {
                throw new Exception('Approval sudah diproses sebelumnya.');
            }
            $approvalService = app(ApprovalService::class);
            $approvalService->approve($this->approvalHistory, $this->notes ?: null);

            // Update approval history
            // $this->approvalHistory->action = ApprovalAction::Approved;
            // $this->approvalHistory->notes = $this->notes ?: null;
            // $this->approvalHistory->approved_at = now();
            // $this->approvalHistory->save();

            // Check if all approvals are complete
            // $allApproved = $this->request->approvalHistories()
            //     ->where('action', ApprovalAction::Pending)
            //     ->count() === 0;

            // if ($allApproved) {
            //     // All approvals complete - mark request as approved
            //     $this->request->status = DPRStatus::Approved;
            //     $this->request->save();
            // }

            // DB::commit();

            // // Send notifications
            // $notificationService = app(DPRNotificationService::class);
            // $notificationService->sendApprovalActionNotification(
            //     $this->request->fresh(),
            //     $this->approvalHistory->fresh()
            // );

            // Update UI state
            $this->actionCompleted = true;
            $this->actionResult = ApprovalAction::Approved->value;
            $this->showApproveConfirm = false;
        } catch (Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->showApproveConfirm = false;
        }
    }

    /**
     * Reject the request
     */
    public function reject()
    {
        $this->errorMessage = null;

        // Validate notes again
        $this->validate([
            'notes' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Validate approval is still pending
            $this->approvalHistory->refresh();

            if ($this->approvalHistory->action !== ApprovalAction::Pending) {
                throw new Exception('Approval sudah diproses sebelumnya.');
            }

            // Update approval history
            $this->approvalHistory->action = ApprovalAction::Rejected;
            $this->approvalHistory->notes = $this->notes;
            $this->approvalHistory->approved_at = now();
            $this->approvalHistory->save();

            // Update request status to rejected
            $this->request->status = DPRStatus::Rejected;
            $this->request->save();

            DB::commit();

            // Send notifications
            $notificationService = app(DPRNotificationService::class);
            $notificationService->sendApprovalActionNotification(
                $this->request->fresh(),
                $this->approvalHistory->fresh()
            );

            // Update UI state
            $this->actionCompleted = true;
            $this->actionResult = ApprovalAction::Rejected->value;
            $this->showRejectConfirm = false;
        } catch (Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->showRejectConfirm = false;
        }
    }

    /**
     * Format currency
     */
    public function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match ($this->request->status->value) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status text
     */
    public function getStatusText(): string
    {
        return match ($this->request->status->value) {
            'pending' => 'Menunggu Approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => $this->request->status->value,
        };
    }
}
