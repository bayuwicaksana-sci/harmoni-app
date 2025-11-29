<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SettlementStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case WaitingDPRApproval = 'waiting_dpr_approval';
    case Approved = 'approved';
    case WaitingRefund = 'waiting_refund';
    case WaitingConfirmation = 'waiting_confirmation';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Menunggu Proses',
            self::WaitingDPRApproval => 'Menunggu Approval DPR',
            self::Approved => 'Disetujui',
            self::WaitingRefund => 'Menunggu Bukti Pengembalian Dana',
            self::WaitingConfirmation => 'Menunggu Konfirmasi Finance Operator',
            self::Closed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }
}
