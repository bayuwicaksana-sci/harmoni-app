<?php

namespace App\Exports;

use App\Enums\COAType;
use App\Models\Coa;
use App\Models\Program;
use App\Models\ProgramActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DPRTemplateExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new RequestItemSheet($this->data),
            new ReferenceSheet,
            // new ProgramActivitiesSheet($this->data),
            // new ProgramActivityItemsSheet($this->data),
        ];
    }
}

// Client Data Sheet (Step 1)
class RequestItemSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'List Request Item';
    }

    public function headings(): array
    {
        return [
            ['List Request Item'],
            [
                'Kode COA',
                'Aktivitas',
                'Deskripsi Item',
                'Qty',
                'Unit Qty',
                'Harga Per Item',
                'Tipe Request (reimburse/advance)',
                'Nama Bank',
                'Nomor Rekening',
                'Nama Pemilik Rekening',
                'Keterangan',
            ],
        ];
    }

    public function collection()
    {
        $rows = collect();

        // Add PIC rows
        $items = $this->data['requestItems'] ?? [];
        if (! empty($items)) {
            foreach ($items as $item) {
                $rows->push([
                    $item['coa_id'] ? Coa::find((int) $item['coa_id'])->code ?? '' : '',
                    $item['program_activity_id'] ? ProgramActivity::whereCoaId((int) $item['coa_id'])->find((int) $item['program_activity_id'])->code ?? '' : '',
                    $item['item'] ?? '',
                    (int) $item['qty'] ?? '',
                    $item['unit_qty'] ?? '',
                    (string) $item['base_price'] ?? '',
                    $item['payment_type'] ?? '',
                    $item['bank_name'] ?? '',
                    (string) $item['bank_account'] ?? '',
                    $item['account_owner'] ?? '',
                    $item['notes'] ?? '',
                ]);
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 45,
            'C' => 45,
            'D' => 15,
            'E' => 15,
            'F' => 30,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 45,
        ];
    }
}

class ReferenceSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Referensi COA';
    }

    public function headings(): array
    {
        return [
            ['Referensi COA'],
            ['Kode COA', 'Nama COA', 'Nama Aktivitas'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 35,
            'C' => 45,
        ];
    }

    public function collection()
    {
        $employee = Auth::user()->employee;

        // Get client IDs from employee's programs through partnership contracts
        $clientIds = $employee->programs()
            ->with('partnershipContracts.client')
            ->get()
            ->pluck('partnershipContracts')
            ->flatten()
            ->pluck('client_id')
            ->unique();

        // Get all programs from those clients (through partnership contracts)
        $clientProgramIds = Program::whereHas('partnershipContracts', function ($query) use ($clientIds) {
            $query->whereIn('partnership_contracts.client_id', $clientIds);
        })->pluck('id');

        // Get ProgramActivities with Program-type COAs (filtered by client's programs)
        $programActivities = ProgramActivity::active()
            ->whereHas('coa', function ($query) use ($clientProgramIds) {
                $query->where('type', COAType::Program)
                    ->whereIn('program_id', $clientProgramIds);
            })
            ->with('coa')
            ->get()
            ->map(fn ($activity) => [
                $activity->coa->code,
                $activity->name,
            ]);

        // Get all Non-Program COAs
        $nonProgramCoas = COA::where('type', '!=', COAType::Program)
            ->get()
            ->map(fn ($coa) => [
                $coa->code,
                $coa->name,
            ]);

        // Merge both collections

        // dd($programActivities->concat($nonProgramCoas));

        return $programActivities->concat($nonProgramCoas);
    }
}

// // Programs Sheet (Step 2)
// class ProgramsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
// {
//     protected $data;

//     public function __construct(array $data)
//     {
//         $this->data = $data;
//     }

//     public function title(): string
//     {
//         return 'Programs';
//     }

//     public function headings(): array
//     {
//         return [
//             ['Daftar Program'],
//             ['Nama Program', 'Deskripsi Program', 'Email PIC'],
//         ];
//     }

//     public function collection()
//     {
//         $rows = collect();
//         $programs = $this->data['programs'] ?? [];

//         if (!empty($programs)) {
//             foreach ($programs as $program) {
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     // $program['program_category_id'] ?? '',
//                     $program['name'] ?? '',
//                     $program['description'] ?? '',
//                     $program['program_pic'] ?? '',
//                 ]);
//             }
//         } else {
//             // Add empty rows for template
//             for ($i = 0; $i < 5; $i++) {
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     '',
//                     '',
//                     ''
//                 ]);
//             }
//         }

//         return $rows;
//     }

//     public function styles(Worksheet $sheet)
//     {
//         return [
//             1 => ['font' => ['bold' => true, 'size' => 14]],
//             2 => ['font' => ['bold' => true]],
//         ];
//     }
// }

// Program Activities Sheet (Step 3)
// class ProgramActivitiesSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
// {
//     protected $data;

//     public function __construct(array $data)
//     {
//         $this->data = $data;
//     }

//     public function title(): string
//     {
//         return 'Program Activities';
//     }

//     public function headings(): array
//     {
//         return [
//             ['Aktivitas Program'],
//             ['Program Name', 'Nama Aktivitas', 'Estimasi Tanggal Mulai', 'Estimasi Tanggal Selesai'],
//         ];
//     }

//     public function collection()
//     {
//         $rows = collect();
//         $activities = $this->data['program_activities'] ?? [];

//         if (!empty($activities)) {
//             foreach ($activities as $activity) {
//                 // dd($this->data['programs']);
//                 $programFiltered = array_filter($this->data['programs'], function ($program) use ($activity) {
//                     return implode('-', [$this->data['code'], str_replace(' ', '-', $program['name'])]) === $activity['program_code'];
//                 });

//                 $programKey = array_key_first($programFiltered);

//                 $programName = $programKey ? $programFiltered[$programKey]['name'] : '';
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     $programName,
//                     $activity['name'] ?? '',
//                     $activity['est_start_date'] ?? '',
//                     $activity['est_end_date'] ?? '',
//                 ]);
//             }
//         } else {
//             // Add empty rows for template
//             for ($i = 0; $i < 10; $i++) {
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     '',
//                     '',
//                     '',
//                     ''
//                 ]);
//             }
//         }

//         return $rows;
//     }

//     public function styles(Worksheet $sheet)
//     {
//         return [
//             1 => ['font' => ['bold' => true, 'size' => 14]],
//             2 => ['font' => ['bold' => true]],
//         ];
//     }
// }

// Program Activity Items Sheet (Step 4)
// class ProgramActivityItemsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
// {
//     protected $data;

//     public function __construct(array $data)
//     {
//         $this->data = $data;
//     }

//     public function title(): string
//     {
//         return 'Activity Items';
//     }

//     public function headings(): array
//     {
//         return [
//             ['Item Aktivitas Program'],
//             ['Aktivitas', 'Deskripsi', 'Quantity', 'Unit Qty', 'Frekuensi', 'Budget (IDR)', 'Planned Budget (IDR)'],
//         ];
//     }

//     public function collection()
//     {
//         $rows = collect();
//         $items = $this->data['program_activity_items'] ?? [];

//         if (!empty($items)) {
//             foreach ($items as $item) {
//                 $programActivityFiltered = array_filter($this->data['program_activities'], function ($programActivity) use ($item) {

//                     $programActivityCode = implode('-', [$programActivity['program_code'], $this->data['contract_year'], Str::slug($programActivity['name'])]);

//                     // dd($programActivityCode);

//                     return $programActivityCode === $item['program_activity_code'];
//                 });

//                 $programActivityKey = array_key_first($programActivityFiltered);

//                 $programActivityName = $programActivityKey ? $programActivityFiltered[$programActivityKey]['name'] : '';
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     $programActivityName,
//                     $item['description'] ?? '',
//                     $item['quantity'] ?? '',
//                     $item['unit'] ?? '',
//                     $item['frequency'] ?? '',
//                     $item['total_item_budget'] ?? '',
//                     $item['total_item_planned_budget'] ?? '',
//                 ]);
//             }
//         } else {
//             // Add empty rows for template
//             for ($i = 0; $i < 20; $i++) {
//                 $rows->push([
//                     // $this->data['code'] ?? '',
//                     '',
//                     '',
//                     '',
//                     '',
//                     '',
//                     '',
//                     ''
//                 ]);
//             }
//         }

//         return $rows;
//     }

//     public function styles(Worksheet $sheet)
//     {
//         return [
//             1 => ['font' => ['bold' => true, 'size' => 14]],
//             2 => ['font' => ['bold' => true]],
//         ];
//     }
// }
