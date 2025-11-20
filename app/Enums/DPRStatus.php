<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DPRStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
    case Settled = 'settled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Paid => 'Dibayarkan',
            self::Settled => 'Settled',
        };
    }
}
