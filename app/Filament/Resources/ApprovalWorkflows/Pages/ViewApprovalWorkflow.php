<?php

namespace App\Filament\Resources\ApprovalWorkflows\Pages;

use App\Filament\Resources\ApprovalWorkflows\ApprovalWorkflowResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalWorkflow extends ViewRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
