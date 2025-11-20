<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApproverType: string implements HasLabel, HasColor
{
    // case Asset = 'asset';
    // case Liability = 'liability';
    case Supervisor = 'supervisor';
    case JobLevel = 'job_level';
    case JobTitle = 'job_title';
    // case Revenue = 'revenue';

    public function getLabel(): ?string
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::Supervisor => 'Supervisor',
            self::JobLevel => 'Job Level',
            self::JobTitle => 'Job Title',
            // self::Revenue => 'Pendapatan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            // self::Asset => 'Aset',
            // self::Liability => 'Liabilitas',
            self::Supervisor => 'info',
            self::JobLevel => 'warning',
            self::JobTitle => 'success',
            // self::Revenue => 'Pendapatan',
        };
    }
}
