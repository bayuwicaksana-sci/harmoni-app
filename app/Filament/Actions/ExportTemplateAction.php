<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientWizardTemplateExport;

class ExportTemplateAction
{
    public static function make(): Action
    {
        return Action::make('exportTemplate')
            ->label('Export Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function (array $data) {
                $fileName = 'client_template_' . now()->format('YmdHis') . '.xlsx';

                return Excel::download(
                    new ClientWizardTemplateExport($data),
                    $fileName
                );
            });
    }
}
