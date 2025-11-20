<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClientWizardTemplateImport;
use Filament\Notifications\Notification;

class ImportTemplateAction
{
    public static function make(): Action
    {
        return Action::make('importTemplate')
            ->label('Import Template')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->schema([
                FileUpload::make('file')
                    ->label('Excel File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel'
                    ])
                    ->required(),
            ])
            ->action(function (array $data, $livewire) {
                try {
                    $file = storage_path('app/public/' . $data['file']);
                    $import = new ClientWizardTemplateImport();
                    Excel::import($import, $file);

                    $importedData = $import->getImportedData();

                    // Fill the form with imported data
                    $livewire->form->fill($importedData);

                    Notification::make()
                        ->success()
                        ->title('Import Successful')
                        ->body('Data has been imported successfully.')
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Import Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
