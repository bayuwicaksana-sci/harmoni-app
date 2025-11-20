<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestPaymentType: string implements HasLabel, HasColor
{
    case Advance = 'advance';
    case Reimburse = 'reimburse';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Advance => 'Advance Payment',
            self::Reimburse => 'Reimburse',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Advance => 'warning',
            self::Reimburse => 'info',
        };
    }
}
