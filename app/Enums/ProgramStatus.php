<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProgramStatus: string implements HasLabel, HasColor
{
    case Planning = 'planning';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Planning => 'secondary',
            self::Active => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
