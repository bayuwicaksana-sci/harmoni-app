<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApprovalAction: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revised = 'revised';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Revised => 'Direvisi',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Pending => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Revised => 'warning',
        };
    }
}
