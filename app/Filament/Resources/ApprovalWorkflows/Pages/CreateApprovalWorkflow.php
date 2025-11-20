<?php

namespace App\Filament\Resources\ApprovalWorkflows\Pages;

use App\Filament\Resources\ApprovalWorkflows\ApprovalWorkflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApprovalWorkflow extends CreateRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Approval workflow created. Now add approval rules.';
    }
}
