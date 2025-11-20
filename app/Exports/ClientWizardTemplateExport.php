<?php

namespace App\Exports;

use App\Models\ProgramCategory;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientWizardTemplateExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new ClientDataSheet($this->data),
            new ProgramsSheet($this->data),
            new ProgramActivitiesSheet($this->data),
            new ProgramActivityItemsSheet($this->data),
            new ProgramCategoriesReferenceSheet,
        ];
    }
}

// Client Data Sheet (Step 1)
class ClientDataSheet implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Client Data';
    }

    public function headings(): array
    {
        return [
            ['Informasi Klien'],
            ['Nama Klien', 'Slug'],
            [$this->data['name'] ?? '', $this->data['code'] ?? ''],
            [],
            ['Klien PICs'],
            ['Jabatan PIC', 'Nama PIC', 'Email', 'No. HP'],
        ];
    }

    public function collection()
    {
        $rows = collect();

        // Add PIC rows
        $pics = $this->data['client_pic'] ?? [];
        if (! empty($pics)) {
            foreach ($pics as $pic) {
                $rows->push([
                    $pic['pic_position'] ?? '',
                    $pic['pic_name'] ?? '',
                    $pic['pic_email'] ?? '',
                    $pic['pic_phone'] ?? '',
                ]);
            }
        } else {
            // Add empty rows for template
            for ($i = 0; $i < 3; $i++) {
                $rows->push(['', '', '']);
            }
        }

        $rows->push(['']);
        $rows->push(['Kontrak Kerja']);
        $rows->push(['Nomor Kontrak', 'Tahun Kontrak', 'Tanggal Awal Periode', 'Tanggal Akhir Periode']);
        $rows->push([
            $this->data['contract_code'] ?? '',
            $this->data['contract_year'] ?? '',
            $this->data['start_date'] ?? '',
            $this->data['end_date'] ?? '',
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true, 'size' => 14]],
            6 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
            },
        ];
    }
}

// Programs Sheet (Step 2)
class ProgramsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Programs';
    }

    public function headings(): array
    {
        return [
            ['Daftar Program'],
            ['Kategori Program', 'Nama Program', 'Deskripsi Program', 'Email PIC'],
        ];
    }

    public function collection()
    {
        $rows = collect();
        $programs = $this->data['programs'] ?? [];

        if (! empty($programs)) {
            foreach ($programs as $program) {
                $categoryName = '';
                if (! empty($program['program_category_id'])) {
                    $category = ProgramCategory::find($program['program_category_id']);
                    $categoryName = $category?->name ?? '';
                }

                $rows->push([
                    $categoryName,
                    $program['name'] ?? '',
                    $program['description'] ?? '',
                    $program['program_pic'] ?? '',
                ]);
            }
        } else {
            // Add empty rows for template
            for ($i = 0; $i < 5; $i++) {
                $rows->push([
                    '',
                    '',
                    '',
                    '',
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
}

// Program Activities Sheet (Step 3)
class ProgramActivitiesSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Program Activities';
    }

    public function headings(): array
    {
        return [
            ['Aktivitas Program'],
            ['Program Name', 'Nama Aktivitas', 'Estimasi Tanggal Mulai', 'Estimasi Tanggal Selesai'],
        ];
    }

    public function collection()
    {
        $rows = collect();
        $activities = $this->data['program_activities'] ?? [];

        if (! empty($activities)) {
            foreach ($activities as $activity) {
                // dd($this->data['programs']);
                $programFiltered = array_filter($this->data['programs'], function ($program) use ($activity) {
                    return implode('-', [$this->data['code'], str_replace(' ', '-', $program['name'])]) === $activity['program_code'];
                });

                $programKey = array_key_first($programFiltered);

                $programName = $programKey ? $programFiltered[$programKey]['name'] : '';
                $rows->push([
                    // $this->data['code'] ?? '',
                    $programName,
                    $activity['name'] ?? '',
                    $activity['est_start_date'] ?? '',
                    $activity['est_end_date'] ?? '',
                ]);
            }
        } else {
            // Add empty rows for template
            for ($i = 0; $i < 10; $i++) {
                $rows->push([
                    // $this->data['code'] ?? '',
                    '',
                    '',
                    '',
                    '',
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
}

// Program Activity Items Sheet (Step 4)
class ProgramActivityItemsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Activity Items';
    }

    public function headings(): array
    {
        return [
            ['Item Aktivitas Program'],
            ['Aktivitas', 'Deskripsi', 'Quantity', 'Unit Qty', 'Frekuensi', 'Budget (IDR)', 'Planned Budget (IDR)'],
        ];
    }

    public function collection()
    {
        $rows = collect();
        $items = $this->data['program_activity_items'] ?? [];

        if (! empty($items)) {
            foreach ($items as $item) {
                $programActivityFiltered = array_filter($this->data['program_activities'], function ($programActivity) use ($item) {

                    $programActivityCode = implode('-', [$programActivity['program_code'], $this->data['contract_year'], Str::slug($programActivity['name'])]);

                    // dd($programActivityCode);

                    return $programActivityCode === $item['program_activity_code'];
                });

                $programActivityKey = array_key_first($programActivityFiltered);

                $programActivityName = $programActivityKey ? $programActivityFiltered[$programActivityKey]['name'] : '';
                $rows->push([
                    // $this->data['code'] ?? '',
                    $programActivityName,
                    $item['description'] ?? '',
                    $item['quantity'] ?? '',
                    $item['unit'] ?? '',
                    $item['frequency'] ?? '',
                    $item['total_item_budget'] ?? '',
                    $item['total_item_planned_budget'] ?? '',
                ]);
            }
        } else {
            // Add empty rows for template
            for ($i = 0; $i < 20; $i++) {
                $rows->push([
                    // $this->data['code'] ?? '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
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
}

// Program Categories Reference Sheet
class ProgramCategoriesReferenceSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Referensi Kategori';
    }

    public function headings(): array
    {
        return [
            ['Daftar Kategori Program'],
            ['No.', 'Nama Kategori'],
        ];
    }

    public function collection()
    {
        $categories = ProgramCategory::all();

        return $categories->map(function ($category) {
            return [
                $category->id,
                $category->name,
            ];
        });
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}
