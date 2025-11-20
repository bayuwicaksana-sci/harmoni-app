<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Coa;
use Filament\Resources\Pages\CreateRecord;

class CreateProgram extends CreateRecord
{
    protected static string $resource = ProgramResource::class;

    protected function afterCreate(): void
    {
        // Create COA for this program
        Coa::create([
            'code' => $this->record->code,
            'name' => $this->record->name,
            'type' => 'program',
            'program_id' => $this->record->id,
            'is_active' => true,
        ]);
    }
}
