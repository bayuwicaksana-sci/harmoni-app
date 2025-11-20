<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PartnershipContractStatus: string implements HasLabel, HasColor
{
    // , ['draft', 'active', 'completed', 'terminated']
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Terminated = 'terminated';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Terminated => 'Dihentikan',
        };
    }
    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Active => 'primary',
            self::Completed => 'success',
            self::Terminated => 'danger',
        };
    }
}
