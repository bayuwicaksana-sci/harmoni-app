<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum COAType: string implements HasLabel, HasColor
{
    // case Asset = 'asset';
    // case Liability = 'liability';
    case NonProgram = 'non_program';
    case Program = 'program';
    // case Revenue = 'revenue';

    public function getLabel(): ?string
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::NonProgram => 'Non-Program',
            self::Program => 'Program',
            // self::Revenue => 'Pendapatan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::NonProgram => 'warning',
            self::Program => 'success',
            // self::Revenue => 'Pendapatan',
        };
    }
}
