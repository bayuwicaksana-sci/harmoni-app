<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Schemas\DepartmentCreateForm;
use App\Models\Department;
use App\Models\JobTitle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected ?array $jobTitles;

    protected ?Department $createdDepartment;

    public function form(Schema $schema): Schema
    {
        return DepartmentCreateForm::configure($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->jobTitles = array_filter($data['job_titles']);
        unset($data['job_titles']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $this->createdDepartment = static::getModel()::create($data);

        return $this->createdDepartment;
    }

    public function afterCreate(): void
    {
        foreach ($this->jobTitles as $index => $jobTitle) {
            $splittedWords = preg_split("/[\s,_-]+/", $jobTitle['title']);

            $code = '';
            foreach ($splittedWords as $w) {
                $code .= ucfirst(mb_substr($w, 0, 1));
            }
            $jobTitle['code'] = $code;
            $jobTitle['department_id'] = $this->createdDepartment->id;

            JobTitle::create($jobTitle);
        }
    }
}
