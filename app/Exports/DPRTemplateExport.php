<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Str;

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
            // new ProgramsSheet($this->data),
            // new ProgramActivitiesSheet($this->data),
            // new ProgramActivityItemsSheet($this->data),
        ];
    }
}

// Client Data Sheet (Step 1)
class RequestItemSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithEvents
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
            ['Deskripsi Item', 'Qty', 'Unit Qty', 'Harga Per Item', 'Tipe Request (reimburse/advance)', 'Nama Bank', 'Nomor Rekening', 'Nama Pemilik Rekening']
        ];
    }

    public function collection()
    {
        $rows = collect();

        // Add PIC rows
        $items = $this->data['requestItems'] ?? [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $rows->push([
                    $item['item'] ?? '',
                    (int) $item['qty'] ?? '',
                    $item['unit_qty'] ?? '',
                    (string) $item['base_price'] ?? '',
                    $item['payment_type'] ?? '',
                    $item['bank_name'] ?? '',
                    (string) $item['bank_account'] ?? '',
                    $item['account_owner'] ?? '',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(50);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(10);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(40);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(30);
            },
        ];
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
