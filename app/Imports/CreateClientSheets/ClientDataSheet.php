<?php

namespace App\Imports\CreateClientSheets;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class ClientDataSheet implements OnEachRow
{
    protected $contractHeaderFound = false;

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Client Information (row 3 in Excel)
        if ($rowIndex === 3) {
            return $row;
        }

        // Find "Nomor Kontrak" header and read next row
        if (isset($rowData[0]) && $rowData[0] == 'Nomor Kontrak') {
            $this->contractHeaderFound = $rowIndex;
        }

        // Contract data (row after "Nomor Kontrak")
        if (is_int($this->contractHeaderFound) && $rowIndex === $this->contractHeaderFound + 1) {
            // $this->data['contract_code'] = $rowData[0] ?? '';
            // $this->data['contract_year'] = $rowData[1] ?? '';
            // $this->data['start_date'] = ! empty($rowData[2]) ? (is_numeric($rowData[2]) ? Date::excelToDateTimeObject($rowData[2])->format('Y-m-d') : Carbon::parse($rowData[2])->format('Y-m-d')) : '';
            // $this->data['end_date'] = ! empty($rowData[3]) ? (is_numeric($rowData[3]) ? Date::excelToDateTimeObject($rowData[3])->format('Y-m-d') : Carbon::parse($rowData[3])->format('Y-m-d')) : '';
            return $row;
        }

        return null;
    }
}
