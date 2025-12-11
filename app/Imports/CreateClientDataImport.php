<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\ClientPic;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class CreateClientDataImport implements ToCollection, WithCalculatedFormulas
{
    protected $contractHeaderFound;

    public function collection(Collection $rows)
    {
        $createdClient = new Client;
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 1 && ($row[0] !== '' || $row[0] !== null)) {
                $createdClient->name = $row[0];
                if ($row[1] && Client::where('code', $row[1])->get() === null) {
                    $createdClient->code = $row[1];
                } else {
                    preg_match_all('/\b\w/u', $row[1], $matches);
                    $autoCode = implode('', $matches[0]);

                    if (Client::where('code', $autoCode)->get() === null) {
                        $createdClient->code = strtoupper($autoCode);
                    } else {
                        $createdClient->code = strtoupper(implode('', [...$matches[0], (string) now('Asia/Jakarta')->timestamp]));
                    }
                }

                $createdClient->save();
                $createdClient->refresh();
            }

            if ($rowIndex >= 4 && ($row[0] || $row[1] || $row[2] || $row[3])) {
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                    return;
                }

                if ($row[0] == 'Nomor Kontrak') {
                    $this->contractHeaderFound = true;

                    return;
                }

                if (! $this->contractHeaderFound && (! empty($row[0]) || ! empty($row[1]))) {
                    ClientPic::create([
                        'client_id' => $createdClient->id,
                        'position' => $row[0] ?? '',
                        'name' => $row[1] ?? '',
                        'email' => $row[2] ?? '',
                        'phone' => (string) ($row[3] ?? ''),
                    ]);
                }
            }

            // Find "Nomor Kontrak" header and read next row
            if (isset($rowData[0]) && $rowData[0] == 'Nomor Kontrak') {
                $this->contractHeaderFound = $rowIndex;
            }
        }
    }
}
