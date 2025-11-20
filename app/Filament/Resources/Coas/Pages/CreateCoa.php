<?php

namespace App\Filament\Resources\Coas\Pages;

use App\Enums\COAType;
use App\Filament\Resources\Coas\CoaResource;
use App\Models\PartnershipContract;
use App\Models\Program;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCoa extends CreateRecord
{
    protected static string $resource = CoaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $coaCode = "";

        if ($data['type'] === COAType::Program) {
            $program = Program::find($data['program_id']);
            // $partnershipContract = PartnershipContract::find(3);
            $partnershipContract = PartnershipContract::find($data['partnership_contract_id']);

            $programContracts = $program->partnershipContracts;

            if (!$programContracts->contains($partnershipContract)) {
                // throw ValidationException::withMessages(["partnership_contract_id" => 'Terjadi Kesalahan. Pilih kembali']);

                Notification::make()
                    ->danger()
                    ->title('Validation Error')
                    ->body('Terjadi Kesalahan. Periksa kembali data formulir')
                    ->persistent()
                    ->send();

                $this->halt(true);
            }

            $coaCode = implode('-', [$partnershipContract->client->code, str_replace(' ', '-', $program->name), $partnershipContract->contract_year]);

            $data['contract_year'] = (int) $data['contract_year'];

            unset($data['partnership_contract_id']);
        } else {
            if (isset($data['program_id'])) {
                unset($data['program_id']);
            }
            if (isset($data['partnership_contract_id'])) {
                unset($data['partnership_contract_id']);
            }

            $coaCode = implode('-', ['EXP', str_replace(' ', '-', $data['name'])]);
        }

        $data['code'] = $coaCode;
        // dd($data, $coaCode);
        return $data;
    }
}
