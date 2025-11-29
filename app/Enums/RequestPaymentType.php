<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestPaymentType: string implements HasColor, HasLabel
{
    case Advance = 'advance';
    case Reimburse = 'reimburse';
    case Offset = 'offset';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Advance => 'Advance Payment',
            self::Reimburse => 'Reimburse',
            self::Offset => 'Offset',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Advance => 'warning',
            self::Reimburse => 'info',
            self::Offset => 'gray',
        };
    }

    // Method to get filtered options
    public static function getSelectOptions(array $exclude = []): array
    {
        return collect(self::cases())
            ->reject(fn ($case) => in_array($case, $exclude))
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
