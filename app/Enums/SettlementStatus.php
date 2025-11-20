<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SettlementStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Ditunda',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
