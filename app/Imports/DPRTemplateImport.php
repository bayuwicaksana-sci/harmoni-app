<?php

namespace App\Imports;

use App\Enums\RequestPaymentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DPRTemplateImport implements WithMultipleSheets
{
    // protected $importedData = [];

    public function sheets(): array
    {
        return [
            0 => new RequestItemImport,
        ];
    }

    // public function getImportedData(): array
    // {
    //     return $this->importedData;
    // }
}

// Import Program Activity Items Sheet
class RequestItemImport implements ToCollection, WithCalculatedFormulas
{
    // protected $data;

    // public function __construct(&$data)
    // {
    //     $this->data = &$data;
    // }

    // public function collection($rows)
    // {
    //     $items = [];

    //     // Start from row 2 (after headers)
    //     foreach ($rows as $index => $row) {
    //         if ($index < 2) {
    //             continue;
    //         }

    //         $items[] = [
    //             'coa_id' => null,
    //             'program_activity_id' => null,
    //             'item' => (string) $row[0] ?? '',
    //             'qty' => (int) $row[1] ?? 0,
    //             'unit_qty' => (string) $row[2] ?? '',
    //             'base_price' => (float) $row[3] ?? 0.0,
    //             'total_price' => (float) ($row[3] ?? 0) * (int) ($row[1] ?? 0),
    //             'payment_type' => RequestPaymentType::from($row[4]) ?? 'advance',
    //             'attachments' => [],
    //             // 'base_price' => (float) ($row[3] ? str_replace(['.', ','], ['', '.'], $row[3]) : 0.0),
    //             'self_account' => Auth::user()->employee?->bank_account_number === (string) $row[6],
    //             'bank_name' => Auth::user()->employee?->bank_account_number === (string) $row[6] ? Auth::user()->employee?->bank_name : ((string) $row[5] ?? ''),
    //             'bank_account' => Auth::user()->employee?->bank_account_number === (string) $row[6] ? Auth::user()->employee?->bank_account_number : ((string) $row[6] ?? ''),
    //             'account_owner' => Auth::user()->employee?->bank_account_number === (string) $row[6] ? Auth::user()->employee?->bank_cust_name : ((string) $row[7] ?? ''),
    //         ];
    //     }

    //     $totalRequestAmount = 0;
    //     foreach ($items as $index => $item) {
    //         $totalRequestAmount += $item['total_price'];
    //     }

    //     $this->data['total_request_amount'] = $totalRequestAmount;
    //     $this->data['requestItems'] = $items;
    // }
    public function collection(Collection $collection)
    {
        //
    }
}
