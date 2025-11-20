<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Template with Programs data pre-filled
 * and empty templates for Program Activities and Activity Items
 */
class CreateClientTemplateExport implements WithMultipleSheets
{
    protected $programs;

    public function __construct(array $programs)
    {
        $this->programs = $programs;
    }

    public function sheets(): array
    {
        return [
            new ProgramsReferenceSheet($this->programs),
            new ProgramActivitiesTemplateSheet($this->programs),
            new ProgramActivityItemsTemplateSheet(),
            new InstructionsSheet(),
        ];
    }
}

/**
 * Sheet 1: Programs Reference (READ ONLY - Pre-filled)
 * This contains the programs from Step 1 for reference
 */
class ProgramsReferenceSheet implements
    FromArray,
    WithTitle,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
{
    protected $programs;

    public function __construct(array $programs)
    {
        $this->programs = $programs;
    }

    public function array(): array
    {
        return collect($this->programs)->map(function ($program) {
            return [
                'code' => $program['code'] ?? '',
                'name' => $program['name'] ?? '',
                // 'description' => $program['description'] ?? '',
            ];
        })->toArray();
    }

    public function title(): string
    {
        return 'Programs (Reference)';
    }

    public function headings(): array
    {
        return [
            'Program Code',
            'Program Name',
            // 'Description',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 2: Program Activities Template (Fill this in)
 * Users will fill in product data here
 */
class ProgramActivitiesTemplateSheet implements
    FromArray,
    WithTitle,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $programs;

    public function __construct(array $programs)
    {
        $this->programs = $programs;
    }

    public function array(): array
    {
        // Return 3 example rows to show format
        return [
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
        ];
    }

    public function title(): string
    {
        return 'Program Activities (Fill This)';
    }

    public function headings(): array
    {
        return [
            'Program Code*',
            'Activity Code*',
            'Activity Name*',
            'Est. Start Time',
            'Est. End Time',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Add data validation for program codes
                $programCodeString = implode(',', array_column($this->programs, 'code'));

                $validation = $event->sheet->getDelegate()
                    ->getCell('A2')
                    ->getDataValidation();

                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Invalid Program');
                $validation->setError('Please select a valid program from the list');
                $validation->setPromptTitle('Select Program');
                $validation->setPrompt('Choose from the programs in the Reference sheet');
                $validation->setFormula1('"' . $programCodeString . '"');

                // Apply to multiple rows (up to 500)
                for ($i = 2; $i <= 500; $i++) {
                    $event->sheet->getDelegate()->getCell('A' . $i)->setDataValidation(clone $validation);
                }

                // Add comment/note to explain
                $event->sheet->getDelegate()
                    ->getComment('A1')
                    ->getText()
                    ->createTextRun('Select program code from the dropdown. Available codes are in the "Programs (Reference)" sheet.');
            },
        ];
    }
}

/**
 * Sheet 3: Activity Items Template (Fill this in)
 * Users will fill in variant data here
 */
class ProgramActivityItemsTemplateSheet implements
    FromArray,
    WithTitle,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
{
    public function array(): array
    {
        // Return 3 example rows to show format
        return [
            ['', '', '', '', '', '', ''],
            ['', '', '', '', '', '', ''],
            ['', '', '', '', '', '', ''],
        ];
    }

    public function title(): string
    {
        return 'Activity Items (Fill This)';
    }

    public function headings(): array
    {
        return [
            'Aktivitas*',
            'Deskripsi Item*',
            'Qty*',
            'Unit Qty*',
            'Frequency*',
            'Nilai Kontrak Item*',
            'Nilai Planned Item*',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 4: Instructions
 * Help users understand how to fill the template
 */
class InstructionsSheet implements
    FromArray,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['IMPORT TEMPLATE INSTRUCTIONS'],
            [''],
            ['This template is generated from your Step 1 programs.'],
            [''],
            ['SHEET DESCRIPTIONS:'],
            [''],
            ['1. Programs (Reference) - READ ONLY'],
            ['   • Contains your programs from Step 1'],
            ['   • Use these program codes when filling Program Activities sheet'],
            ['   • DO NOT modify this sheet'],
            [''],
            ['2. Program Activities (Fill This) - FILL THIS IN'],
            ['   • Add your products here (hundreds of rows if needed)'],
            ['   • Program Code: Must match codes from Programs sheet (dropdown available)'],
            ['   • Activity Code: Unique identifier for each product'],
            ['   • Activity Name: Descriptive name'],
            ['   • Price: Numeric value (e.g., 99.99)'],
            ['   • Fields marked with * are REQUIRED'],
            [''],
            ['3. Activity Items (Fill This) - FILL THIS IN'],
            ['   • Add product variants here'],
            ['   • Activity Code: Must match a product code from Program Activities sheet'],
            ['   • Variant Code: Unique identifier for variant'],
            ['   • Variant Name: Description (e.g., "Red", "Large", "128GB")'],
            ['   • SKU: Stock Keeping Unit'],
            ['   • Additional Price: Extra cost for this variant (0 if same as base)'],
            ['   • Fields marked with * are REQUIRED'],
            [''],
            ['IMPORTANT NOTES:'],
            [''],
            ['• Do NOT delete or rename sheets'],
            ['• Do NOT modify header rows'],
            ['• Ensure all required fields (*) are filled'],
            ['• Activity codes in Activity Items must exist in Program Activities sheet'],
            ['• Program codes in Program Activities must exist in Programs sheet'],
            ['• Save file and upload in the wizard'],
            [''],
            ['EXAMPLE DATA:'],
            [''],
            ['Program Activities Example:'],
            ['Program Code | Activity Code | Activity Name        | Price'],
            ['CAT-001      | PROD-001    | Smartphone X        | 699.99'],
            ['CAT-001      | PROD-002    | Laptop Pro          | 1299.99'],
            [''],
            ['Activity Items Example:'],
            ['Activity Code | Variant Code | Variant Name | SKU           | Additional Price'],
            ['PROD-001    | VAR-001     | Red 128GB    | PHONE-RED-128 | 0'],
            ['PROD-001    | VAR-002     | Blue 256GB   | PHONE-BLU-256 | 100'],
        ];
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(80);

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6'],
                ],
            ],
            'A5' => ['font' => ['bold' => true, 'size' => 12]],
            'A27' => ['font' => ['bold' => true, 'size' => 12]],
            'A37' => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
