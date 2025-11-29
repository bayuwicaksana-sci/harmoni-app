<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RequestItemStatus: string implements HasLabel
{
    case Draft = 'draft';
    case WaitingApproval = 'waiting_approval';
    case WaitingPayment = 'waiting_payment';
    case WaitingSettlement = 'waiting_settlement';
    case WaitingSettlementReview = 'waiting_settlement_review';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case WaitingReimburse = 'waiting_reimburse';
    case WaitingRefund = 'waiting_refund';
    case Closed = 'closed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::WaitingApproval => 'Menunggu Persetujuan',
            self::WaitingPayment => 'Menunggu Pencairan',
            self::Closed => 'Terbayarkan/Selesai',
            self::WaitingSettlement => 'Menunggu Settlement',
            self::WaitingSettlementReview => 'Menunggu Review',
            self::Rejected => 'Ditolak',
            self::WaitingReimburse => 'Menunggu Pengembalian Dana',
            self::WaitingRefund => 'Menunggu Bukti Pengembalian Dana',
            self::Cancelled => 'Dibatalkan'
        };
    }
}
