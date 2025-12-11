<?php

namespace App\Imports;

use App\Models\ProgramCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ClientWizardTemplateImport implements WithMultipleSheets
{
    protected $importedData = [];

    public function sheets(): array
    {
        return [
            0 => new ClientDataImport($this->importedData),
            1 => new ProgramsImport($this->importedData),
            2 => new ProgramActivitiesImport($this->importedData),
            3 => new ProgramActivityItemsImport($this->importedData),
        ];
    }

    public function getImportedData(): array
    {
        // dd($this->importedData);
        return $this->importedData;
    }

    // public function chunkSize(): int
    // {
    //     return 10;
    // }
}

// Import Client Data Sheet
class ClientDataImport implements OnEachRow
{
    protected $data;

    protected $contractHeaderFound = false;

    public function __construct(&$data)
    {
        $this->data = &$data;
        $this->data['client_pic'] = [];
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Client Information (row 3 in Excel)
        if ($rowIndex === 3) {
            $this->data['name'] = $rowData[0] ?? '';
            $this->data['code'] = $rowData[1] ?? '';
        }

        // Client PICs (starting from row 7)
        if ($rowIndex >= 7) {
            // Stop at empty row or "WORK CONTRACT" section
            if (empty($rowData[0]) && empty($rowData[1]) && empty($rowData[2])) {
                return;
            }
            if ($rowData[0] == 'Kontrak Kerja') {
                $this->contractHeaderFound = true;

                return;
            }

            if (! $this->contractHeaderFound && (! empty($rowData[0]) || ! empty($rowData[1]))) {
                $this->data['client_pic'][] = [
                    'pic_position' => $rowData[0] ?? '',
                    'pic_name' => $rowData[1] ?? '',
                    'pic_email' => $rowData[2] ?? '',
                    'pic_phone' => (string) ($rowData[3] ?? ''),
                ];
            }
        }

        // Find "Nomor Kontrak" header and read next row
        if (isset($rowData[0]) && $rowData[0] == 'Nomor Kontrak') {
            $this->contractHeaderFound = $rowIndex;
        }

        // Contract data (row after "Nomor Kontrak")
        if (is_int($this->contractHeaderFound) && $rowIndex === $this->contractHeaderFound + 1) {
            $this->data['contract_code'] = $rowData[0] ?? '';
            $this->data['contract_year'] = $rowData[1] ?? '';
            $this->data['start_date'] = ! empty($rowData[2]) ? (is_numeric($rowData[2]) ? Date::excelToDateTimeObject($rowData[2])->format('Y-m-d') : Carbon::parse($rowData[2])->format('Y-m-d')) : '';
            $this->data['end_date'] = ! empty($rowData[3]) ? (is_numeric($rowData[3]) ? Date::excelToDateTimeObject($rowData[3])->format('Y-m-d') : Carbon::parse($rowData[3])->format('Y-m-d')) : '';
        }
    }
}

// Import Programs Sheet
class ProgramsImport implements OnEachRow
{
    protected $data;

    protected static $categoryCache = null;

    public function __construct(&$data)
    {
        $this->data = &$data;
        $this->data['programs'] = [];

        // Cache categories once
        if (static::$categoryCache === null) {
            static::$categoryCache = ProgramCategory::pluck('id', 'name')->toArray();
        }
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Skip header rows (first 2 rows)
        if ($rowIndex <= 2) {
            return;
        }

        // Program Name is required (column 1)
        if (! empty($rowData[1])) {
            $categoryName = $rowData[0] ?? '';
            $programName = $rowData[1];
            $clientCode = $this->data['code'] ?? '';
            $programCode = implode('-', [$clientCode, str_replace(' ', '-', $programName)]);

            // Map category name to ID using cache
            $categoryId = 1; // Default fallback
            if (! empty($categoryName) && isset(static::$categoryCache[$categoryName])) {
                $categoryId = static::$categoryCache[$categoryName];
            }

            $this->data['programs'][] = [
                'program_code' => $programCode,
                'program_category_id' => $categoryId,
                'name' => $programName,
                'description' => $rowData[2] ?? '',
                'program_pic' => $rowData[3] ?? '',
            ];
        }
    }
}

// Import Program Activities Sheet
class ProgramActivitiesImport implements OnEachRow
{
    protected $data;

    public function __construct(&$data)
    {
        $this->data = &$data;
        $this->data['program_activities'] = [];
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Skip header rows (first 2 rows)
        if ($rowIndex <= 2) {
            return;
        }

        // Activity Name is required (column 1)
        if (! empty($rowData[1])) {
            $programName = $rowData[0];
            $activityName = $rowData[1];
            $clientCode = $this->data['code'] ?? '';
            $contractYear = $this->data['contract_year'] ?? '';

            // Generate program code
            $programCode = implode('-', [$clientCode, str_replace(' ', '-', $programName)]);

            // Generate activity code
            $activityCode = implode('-', [$contractYear, $programCode, Str::slug($activityName)]);

            $this->data['program_activities'][] = [
                'program_code' => $programCode,
                'activity_code' => $activityCode,
                'name' => $activityName,
                'est_start_date' => ! empty($rowData[2]) ? (is_numeric($rowData[2]) ? Date::excelToDateTimeObject($rowData[2])->format('Y-m-d') : Carbon::parse($rowData[2])->format('Y-m-d')) : '',
                'est_end_date' => ! empty($rowData[3]) ? (is_numeric($rowData[3]) ? Date::excelToDateTimeObject($rowData[3])->format('Y-m-d') : Carbon::parse($rowData[3])->format('Y-m-d')) : '',
            ];
        }
    }
}

// Import Program Activity Items Sheet
class ProgramActivityItemsImport implements OnEachRow
{
    protected $data;

    protected $activityIndex = [];

    protected $indexBuilt = false;

    public function __construct(&$data)
    {
        $this->data = &$data;
        $this->data['program_activity_items'] = [];
    }

    protected function buildActivityIndex(): void
    {
        if ($this->indexBuilt) {
            return;
        }

        // Build activity index ONCE for O(1) lookup
        // This runs on first row, after ProgramActivitiesImport has been processed
        foreach ($this->data['program_activities'] ?? [] as $activity) {
            // Trim activity name to handle whitespace issues
            $activityName = trim($activity['name'] ?? '');
            if (! empty($activityName)) {
                $this->activityIndex[$activityName] = $activity['activity_code'];
            }
        }

        $this->indexBuilt = true;
    }

    public function onRow(Row $row)
    {
        // Build index on first data row (after sheets 0-2 have been processed)
        $this->buildActivityIndex();

        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        // Skip header rows (first 2 rows)
        if ($rowIndex <= 2) {
            return;
        }

        // Item Description is required (column 1)
        if (! empty($rowData[1])) {
            // Trim activity name to match the trimmed index keys
            $activityName = trim($rowData[0] ?? '');

            // Skip if activity name is empty
            if (empty($activityName)) {
                return;
            }

            // O(1) lookup instead of O(n) array_filter
            $activityCode = $this->activityIndex[$activityName] ?? null;

            // TEMPORARY DEBUG - Remove after testing
            if (! $activityCode) {
                Log::info('Activity not found', [
                    'looking_for' => $activityName,
                    'available_activities' => array_keys($this->activityIndex),
                    'row_data' => $rowData,
                ]);
            }

            if ($activityCode) {
                $this->data['program_activity_items'][] = [
                    'program_activity_code' => $activityCode,
                    'activity_name' => $activityName,
                    'description' => $rowData[1] ?? '',
                    'quantity' => $rowData[2] ?? '',
                    'unit' => $rowData[3] ?? '',
                    'frequency' => $rowData[4] ?? '',
                    'total_item_budget' => $rowData[5] ?? '',
                    'total_item_planned_budget' => $rowData[6] ?? '',
                ];
            }
        }
    }
}

// [
//     [
//         "code" => 'DMF-2025-penanaman-pohon',
//         "program-name" => 'Penanaman Pohon',
//         "activity" => [
//             'code' => 'DMF-2025-penanaman-pohon-pengadaan-bibit',
//             'act-name' => 'Pengadaan Bibit',
//             'item' => [
//                 'item-name' => 'Alpukat',
//                 'price' => 1000000
//             ]
//         ]
//     ]
// ]
