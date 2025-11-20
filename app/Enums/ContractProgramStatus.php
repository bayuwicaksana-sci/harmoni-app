<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContractProgramStatus: string implements HasLabel
{
    case Active = 'active';
    case Completed = 'completed';
    case Suspended = 'suspended';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Suspended => 'Dijeda',
        };
    }
}
