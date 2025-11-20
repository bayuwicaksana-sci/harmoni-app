<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RequestItemStatus: string implements HasLabel
{
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case WaitingSettlement = 'waiting_settlement';
    case Settled = 'settled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WaitingPayment => 'Menunggu Pencairan',
            self::Paid => 'Dicairkan',
            self::WaitingSettlement => 'Menunggu Settlement',
            self::Settled => 'Settled',
        };
    }
}
