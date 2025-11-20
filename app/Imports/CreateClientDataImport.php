<?php

namespace App\Imports;

use App\Imports\CreateClientSheets\ClientDataSheet;
use App\Imports\CreateClientSheets\ClientPICDataSheet;
use App\Imports\CreateClientSheets\ProgramActivityDataSheet;
use App\Imports\CreateClientSheets\ProgramActivityItemDataSheet;
use App\Imports\CreateClientSheets\ProgramDataSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CreateClientDataImport implements ToCollection, WithCalculatedFormulas
{
    // public function sheets(): array
    // {
    //     return [
    //         'Data' => new ClientDataSheet(),
    //         // 'Data Klien' => new ClientPICDataSheet(),
    //         // 'List Program' => new ProgramDataSheet(),
    //         // 'List Aktivitas' => new ProgramActivityDataSheet(),
    //         // 'List Program Item' => new ProgramActivityItemDataSheet()
    //     ];
    // }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        //
    }
}
