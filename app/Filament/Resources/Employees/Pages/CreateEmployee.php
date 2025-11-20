<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Create user first
    //     $user = User::create([
    //         'name' => $data['name'],
    //         'email' => $data['email'],
    //         'password' => $data['password'],
    //     ]);

    //     $data['user_id'] = $user->id;
    //     unset($data['name'], $data['email'], $data['password']); // Remove employee data

    //     return $data;
    // }
}
