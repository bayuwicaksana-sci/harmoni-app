<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxMethod: string implements HasLabel
{
    // case Asset = 'asset';
    // case Liability = 'liability';
    case ToSCI = 'to_sci';
    case To2ndParty = 'to_2nd_party';
    // case Revenue = 'revenue';

    public function getLabel(): ?string
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::ToSCI => 'SCI',
            self::To2ndParty => 'Pihak Kedua',
            // self::Revenue => 'Pendapatan',
        };
    }
}
