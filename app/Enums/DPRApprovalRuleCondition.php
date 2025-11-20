<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DPRApprovalRuleCondition: string implements HasLabel
{
    // case Asset = 'asset';
    // case Liability = 'liability';
    case Amount = 'amount';
    case Always = 'always';
    // case Revenue = 'revenue';

    public function getLabel(): ?string
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::Amount => 'Dibutuhkan jika memenuhi batas Total Request',
            self::Always => 'Wajib Dibutuhkan',
            // self::Revenue => 'Pendapatan',
        };
    }
}
