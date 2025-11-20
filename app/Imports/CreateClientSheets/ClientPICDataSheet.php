<?php

namespace App\Imports\CreateClientSheets;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class ClientPICDataSheet implements OnEachRow
{
    protected $contractHeaderFound = false;

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Client PICs (starting from row 7)
        if ($rowIndex >= 7) {
            // Stop at empty row or "WORK CONTRACT" section
            if (empty($rowData[0]) && empty($rowData[1]) && empty($rowData[2])) {
                return null;
            }
            if ($rowData[0] == 'Kontrak Kerja') {
                $this->contractHeaderFound = true;
                return null;
            }

            if (! $this->contractHeaderFound && (! empty($rowData[0]) || ! empty($rowData[1]))) {
                return $row;
            }
        }

        return null;
    }
}
