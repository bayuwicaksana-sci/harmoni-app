<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Enums\COAType;
use App\Exports\ClientWizardTemplateExport;
use App\Filament\Resources\Clients\ClientResource;
use App\Imports\ClientWizardTemplateImport;
use App\Imports\CreateClientDataImport;
use App\Models\Client;
use App\Models\ClientPic;
use App\Models\Coa;
use App\Models\Employee;
use App\Models\PartnershipContract;
use App\Models\Program;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\ProgramCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected static ?array $programOptionsCache = null;

    protected static ?array $activityOptionsCache = null;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                CreateAction::make()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->label('Isi Formulir')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->steps([
                        Step::make('Detail Klien dan Kontrak Kerja')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        Fieldset::make('Detail Klien')
                                            ->columns(1)
                                            ->schema([
                                                TextInput::make('name')
                                                    // ->required()
                                                    ->maxLength(255)
                                                    ->label('Nama Klien')
                                                    ->trim(),

                                                TextInput::make('code')
                                                    // ->required()
                                                    ->label('Kode Unik Klien')
                                                    ->unique(ignoreRecord: true)
                                                    ->live(debounce: 700)
                                                    ->maxLength(50)
                                                    ->placeholder('e.g., DMF')
                                                    ->helperText('Kode Unik Klien sebagai referensi'),
                                            ]),
                                        Fieldset::make('Informasi Kontrak Kerja')
                                            ->columns(1)
                                            ->schema([
                                                TextInput::make('contract_code')
                                                    ->label('Nomor Kontrak')
                                                    ->trim(),
                                                TextInput::make('contract_year')
                                                    // ->required()
                                                    ->label('Tahun Kontrak')
                                                    ->numeric()
                                                    ->live(debounce: 700)
                                                    ->minValue(2020)
                                                    ->maxValue(2050)
                                                    ->default(now()->year),

                                                Grid::make()
                                                    ->schema([
                                                        DatePicker::make('start_date')
                                                            // ->required()
                                                            ->native(false)
                                                            ->label('Awal Periode')
                                                            ->default(today()->toDateString())
                                                            ->displayFormat('j M Y'),

                                                        DatePicker::make('end_date')
                                                            // ->required()
                                                            ->native(false)
                                                            ->label('Akhir Periode')
                                                            ->displayFormat('j M Y')
                                                            ->default(today()->addYear()->toDateString())
                                                            ->after('start_date'),
                                                    ]),
                                            ]),
                                        Fieldset::make('PIC Klien')
                                            ->columnSpanFull()
                                            ->schema([
                                                Repeater::make('client_pic')
                                                    ->hiddenLabel()
                                                    ->table(
                                                        [
                                                            TableColumn::make('Jabatan'),
                                                            TableColumn::make('Nama PIC'),
                                                            TableColumn::make('Email PIC'),
                                                            TableColumn::make('No. HP PIC'),
                                                        ]
                                                    )
                                                    ->schema([
                                                        TextInput::make('pic_position')
                                                            ->placeholder('Jabatan PIC')
                                                            ->trim(),
                                                        TextInput::make('pic_name')
                                                            ->placeholder('Ketikkan nama PIC')
                                                            ->trim(),
                                                        TextInput::make('pic_email')
                                                            ->placeholder('pic@mail.com')
                                                            ->trim()
                                                            ->email(),
                                                        TextInput::make('pic_phone')
                                                            ->placeholder('081234567890')
                                                            ->trim()
                                                            ->tel(),
                                                    ])
                                                    ->columnSpanFull()
                                                    ->defaultItems(3)
                                                    ->columns(3)
                                                    ->reorderable(true),
                                            ]),
                                        SpatieMediaLibraryFileUpload::make('documents')
                                            ->label('Dokumen')
                                            ->acceptedFileTypes([
                                                'image/png',
                                                'image/jpeg',
                                                'application/pdf',
                                                'application/msword',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                'application/vnd.ms-excel',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                            ])
                                            ->multiple()
                                            ->appendFiles()
                                            ->maxSize(10240)
                                            ->maxFiles(10)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Step::make('Daftar Program')
                            ->schema([
                                Repeater::make('programs')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Kategori Program'),
                                        TableColumn::make('Nama Program'),
                                        TableColumn::make('Deskripsi'),
                                        TableColumn::make('PIC'),
                                    ])
                                    ->compact()
                                    ->schema([
                                        Select::make('program_category_id')
                                            // ->disabled(fn(Get $get) => null === $get('name'))
                                            // ->required(fn(Get $get) => null !== $get('name'))
                                            ->options(ProgramCategory::query()->pluck('name', 'id'))
                                            ->default(1)
                                            ->selectablePlaceholder(false),
                                        TextInput::make('name')
                                            ->trim()
                                            ->placeholder('Ketikan nama program'),
                                        // ->live(debounce: 700),
                                        // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                        // ->dehydrated(fn($state) => null !== $state),
                                        TextInput::make('description')
                                            ->trim()
                                            ->placeholder('Deskripsi program'),
                                        Select::make('program_pic')
                                            ->options(function () {
                                                static $options;
                                                if (! isset($options)) {
                                                    $options = Employee::query()
                                                        ->select('users.name', 'users.email')
                                                        ->join('users', 'employees.user_id', '=', 'users.id')
                                                        ->join('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
                                                        ->join('departments', 'job_titles.department_id', '=', 'departments.id')
                                                        ->where('departments.code', 'PROG')
                                                        ->pluck('users.name', 'users.email');
                                                }

                                                return $options;
                                            }),

                                    ])
                                    ->defaultItems(0)
                                    ->columns(2)
                                    ->reorderable(false),
                            ]),
                        Step::make('Daftar Aktivitas Program')
                            ->schema([
                                Repeater::make('program_activities')
                                    ->hiddenLabel()
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->table([
                                        TableColumn::make('Program'),
                                        TableColumn::make('Nama Aktivitas'),
                                        TableColumn::make('Estimasi Tanggal Mulai'),
                                        TableColumn::make('Estimasi Tanggal Selesai'),
                                    ])
                                    ->compact()
                                    ->schema([
                                        Select::make('program_code')
                                            // ->disabled(fn(Get $get) => null === $get('name'))
                                            // ->required(fn(Get $get) => null !== $get('name'))
                                            // ->live(debounce: 700)
                                            ->options(function (Get $get) {
                                                // Cache key based on programs + code
                                                $cacheKey = md5(json_encode($get('../../programs')) . ($get('../../code') ?? ''));

                                                if (! isset(static::$programOptionsCache[$cacheKey])) {
                                                    $programs = $get('../../programs');
                                                    $options = [];
                                                    foreach ($programs as $program) {
                                                        if (! empty($program['name'])) {
                                                            $programCode = implode('-', [$get('../../code'), str_replace(' ', '-', $program['name'])]);
                                                            $options[$programCode] = $program['name'];
                                                        }
                                                    }
                                                    static::$programOptionsCache[$cacheKey] = $options;
                                                }

                                                return static::$programOptionsCache[$cacheKey];
                                            }),
                                        TextInput::make('name')
                                            ->trim()
                                            ->placeholder('Ketikan nama aktivitas'),
                                        // ->live(debounce: 700),
                                        // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                        // ->dehydrated(fn($state) => null !== $state),
                                        DatePicker::make('est_start_date')
                                            // ->required()
                                            ->native(false)
                                            ->default(now())
                                            ->displayFormat('j M Y'),

                                        DatePicker::make('est_end_date')
                                            // ->required()
                                            ->native(false)
                                            ->displayFormat('j M Y')
                                            ->default(now()->addYear())
                                            ->after('est_start_date'),

                                    ])
                                    ->columns(2)
                                    ->reorderable(false),
                            ]),
                        Step::make('Rincian Item Aktivitas')
                            ->schema([
                                Repeater::make('program_activity_items')
                                    ->hiddenLabel()
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->table([
                                        TableColumn::make('Aktivitas')->width('150px'),
                                        TableColumn::make('Deskripsi')->width('200px'),
                                        TableColumn::make('Kuantitas'),
                                        TableColumn::make('Satuan'),
                                        TableColumn::make('Frekuensi'),
                                        TableColumn::make('Nilai Kontrak Item')->width('300px'),
                                        TableColumn::make('Nilai Planned Item')->width('300px'),
                                    ])
                                    ->compact()
                                    ->schema([
                                        Select::make('program_activity_code')
                                            // ->disabled(fn(Get $get) => null === $get('name'))
                                            // ->required(fn(Get $get) => null !== $get('name'))
                                            ->options(function (Get $get) {
                                                // Cache key based on activities + contract year
                                                $cacheKey = md5(json_encode($get('../../program_activities')) . ($get('../../contract_year') ?? ''));

                                                if (! isset(static::$activityOptionsCache[$cacheKey])) {
                                                    $activities = $get('../../program_activities');
                                                    $options = [];
                                                    foreach ($activities as $activity) {
                                                        if (! empty($activity['name'])) {
                                                            $coaCode = implode('-', [(string) $get('../../contract_year'), $activity['program_code']]);
                                                            $activityCode = implode('-', [$coaCode, Str::slug($activity['name'])]);
                                                            $options[$activityCode] = $activity['name'];
                                                        }
                                                    }
                                                    static::$activityOptionsCache[$cacheKey] = $options;
                                                }

                                                return static::$activityOptionsCache[$cacheKey];
                                            }),
                                        TextInput::make('description')
                                            ->trim()
                                            ->placeholder('Ketikan deskripsi item'),
                                        // ->live(onBlur: true)
                                        // ->partiallyRenderComponentsAfterStateUpdated(['program_activity_code']),
                                        // ->live(onBlur: true)
                                        // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                        // ->dehydrated(fn($state) => null !== $state),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->minValue(1),
                                        TextInput::make('unit')
                                            ->trim()
                                            ->placeholder('Kg, pcs, dll.'),
                                        TextInput::make('frequency')
                                            ->numeric()
                                            ->minValue(1),
                                        TextInput::make('total_item_budget')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                            ->stripCharacters(['.'])
                                            // ->dehydrateStateUsing(fn($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                                            ->minValue(1),
                                        TextInput::make('total_item_planned_budget')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                            ->stripCharacters(['.'])
                                            // ->dehydrateStateUsing(fn($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                                            ->minValue(1),
                                    ])
                                    ->columns(2)
                                    ->reorderable(false),
                            ]),

                        Step::make('Review')
                            ->schema([
                                Fieldset::make('Detail Klien dan Kontrak Kerja')
                                    ->columns(6)
                                    ->schema([
                                        TextEntry::make('client_name')
                                            ->label('Nama Klien')
                                            ->state(fn(Get $get) => $get('name') . ' (' . $get('code') . ')'),
                                        TextEntry::make('contract_year_review')
                                            ->label('Tahun Kontrak')
                                            ->state(fn(Get $get) => $get('contract_year')),
                                        TextEntry::make('start_date_review')
                                            ->label('Awal Periode')
                                            ->state(fn(Get $get) => $get('start_date'))
                                            ->date('j M Y'),
                                        TextEntry::make('end_date_review')
                                            ->label('Akhir Periode')
                                            ->state(fn(Get $get) => $get('end_date'))
                                            ->date('j M Y'),
                                        TextEntry::make('total_contract_value')
                                            ->label('Nilai Kontrak')
                                            ->state(function (Get $get) {
                                                static $cached = null;
                                                static $cacheKey = null;

                                                $currentKey = md5(json_encode($get('program_activity_items') ?? []));

                                                if ($cached === null || $cacheKey !== $currentKey) {
                                                    $activityItems = $get('program_activity_items') ?? [];
                                                    $totalContractValue = 0;
                                                    $totalPlannedValue = 0;

                                                    foreach ($activityItems as $item) {
                                                        $totalContractValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_budget'] ?? 0);
                                                        $totalPlannedValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_planned_budget'] ?? 0);
                                                    }

                                                    $cached = compact('totalContractValue', 'totalPlannedValue');
                                                    $cacheKey = $currentKey;
                                                }

                                                return $cached['totalContractValue'];
                                            })
                                            ->belowContent(function ($state, Get $get) {
                                                static $cached = null;
                                                static $cacheKey = null;

                                                $currentKey = md5(json_encode($get('program_activity_items') ?? []));

                                                if ($cached === null || $cacheKey !== $currentKey) {
                                                    $activityItems = $get('program_activity_items') ?? [];
                                                    $totalContractValue = 0;
                                                    $totalPlannedValue = 0;

                                                    foreach ($activityItems as $item) {
                                                        $totalContractValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_budget'] ?? 0);
                                                        $totalPlannedValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_planned_budget'] ?? 0);
                                                    }

                                                    $cached = compact('totalContractValue', 'totalPlannedValue');
                                                    $cacheKey = $currentKey;
                                                }

                                                $totalPlannedValue = $cached['totalPlannedValue'];

                                                if ((float) $state === 0.0 || (float) $totalPlannedValue === 0.0) {
                                                    return 'Margin 0%';
                                                }

                                                return 'Margin ' . round(((((float) $state - (float) $totalPlannedValue) / (float) $state) * 100), 2) . '%';
                                            })
                                            ->money(currency: 'IDR', locale: 'id'),
                                        TextEntry::make('total_planned_value')
                                            ->label('Nilai Planned')
                                            ->state(function (Get $get) {
                                                static $cached = null;
                                                static $cacheKey = null;

                                                $currentKey = md5(json_encode($get('program_activity_items') ?? []));

                                                if ($cached === null || $cacheKey !== $currentKey) {
                                                    $activityItems = $get('program_activity_items') ?? [];
                                                    $totalContractValue = 0;
                                                    $totalPlannedValue = 0;

                                                    foreach ($activityItems as $item) {
                                                        $totalContractValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_budget'] ?? 0);
                                                        $totalPlannedValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_planned_budget'] ?? 0);
                                                    }

                                                    $cached = compact('totalContractValue', 'totalPlannedValue');
                                                    $cacheKey = $currentKey;
                                                }

                                                return $cached['totalPlannedValue'];
                                            })
                                            ->belowContent(function ($state, Get $get) {
                                                static $cached = null;
                                                static $cacheKey = null;

                                                $currentKey = md5(json_encode($get('program_activity_items') ?? []));

                                                if ($cached === null || $cacheKey !== $currentKey) {
                                                    $activityItems = $get('program_activity_items') ?? [];
                                                    $totalContractValue = 0;
                                                    $totalPlannedValue = 0;

                                                    foreach ($activityItems as $item) {
                                                        $totalContractValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_budget'] ?? 0);
                                                        $totalPlannedValue += (float) str_replace(['.', ','], ['', '.'], $item['total_item_planned_budget'] ?? 0);
                                                    }

                                                    $cached = compact('totalContractValue', 'totalPlannedValue');
                                                    $cacheKey = $currentKey;
                                                }

                                                $totalContractValue = $cached['totalContractValue'];

                                                if ((float) $state === 0.0 || (float) $totalContractValue === 0.0) {
                                                    return '0%';
                                                }

                                                return round((((float) $state / (float) $totalContractValue) * 100), 2) . '%';
                                            })
                                            ->money(currency: 'IDR', locale: 'id'),
                                        RepeatableEntry::make('pics')
                                            ->columnSpanFull()
                                            ->label('PIC Klien')
                                            ->state(function (Get $get) {
                                                return $get('client_pic');
                                            })
                                            ->table([
                                                TableColumn::make('Jabatan PIC'),
                                                TableColumn::make('Nama PIC'),
                                                TableColumn::make('Email PIC'),
                                                TableColumn::make('No. HP PIC'),
                                            ])
                                            ->schema([
                                                TextEntry::make('pic_position'),
                                                TextEntry::make('pic_name'),
                                                TextEntry::make('pic_email'),
                                                TextEntry::make('pic_phone'),
                                            ])
                                            ->contained(false),
                                    ]),
                                RepeatableEntry::make('programs_review')
                                    ->label('Daftar Program')
                                    ->state(function (Get $get) {
                                        // Build indexes for O(1) lookup instead of nested loops
                                        $activitiesIndex = [];
                                        foreach (array_filter($get('program_activities') ?? [], fn($a) => ! empty($a['name'])) as $activity) {
                                            $coaCode = implode('-', [(string) $get('contract_year'), $activity['program_code']]);
                                            $activityCode = implode('-', [$coaCode, Str::slug($activity['name'])]);
                                            $activitiesIndex[$activity['program_code']][] = [
                                                'code' => $activityCode,
                                                'name' => $activity['name'],
                                            ];
                                        }

                                        $itemsIndex = [];
                                        foreach (array_filter($get('program_activity_items') ?? [], fn($i) => ! empty($i['description'])) as $item) {
                                            // Parse budget values once and format for display
                                            $budgetValue = (float) str_replace(['.', ','], ['', '.'], $item['total_item_budget'] ?? 0);
                                            $plannedValue = (float) str_replace(['.', ','], ['', '.'], $item['total_item_planned_budget'] ?? 0);

                                            $itemsIndex[$item['program_activity_code']][] = [
                                                'description' => $item['description'],
                                                'quantity' => $item['quantity'],
                                                'unit' => $item['unit'],
                                                'frequency' => $item['frequency'],
                                                'total_item_budget' => $budgetValue,
                                                'total_item_planned_budget' => $plannedValue,
                                                // Pre-format currency for Blade view
                                                'total_item_budget_formatted' => 'Rp ' . number_format($budgetValue, 2, ',', '.'),
                                                'total_item_planned_budget_formatted' => 'Rp ' . number_format($plannedValue, 2, ',', '.'),
                                            ];
                                        }

                                        // Single pass through programs - O(n) instead of O(n³)
                                        $results = [];
                                        foreach (array_filter($get('programs') ?? [], fn($p) => ! empty($p['name'])) as $program) {
                                            $programCode = implode('-', [$get('code'), str_replace(' ', '-', $program['name'])]);
                                            $program['code'] = $programCode;
                                            $program['program_activity_items_review'] = [];
                                            $program['contract_budget'] = 0;
                                            $program['planned_budget'] = 0;

                                            // Lookup activities for this program
                                            foreach ($activitiesIndex[$programCode] ?? [] as $activity) {
                                                // Lookup items for this activity
                                                foreach ($itemsIndex[$activity['code']] ?? [] as $item) {
                                                    $program['contract_budget'] += $item['total_item_budget'];
                                                    $program['planned_budget'] += $item['total_item_planned_budget'];
                                                    $item['program_activity_name'] = $activity['name'];
                                                    $program['program_activity_items_review'][] = $item;
                                                }
                                            }

                                            $results[] = $program;
                                        }

                                        return $results;
                                    })
                                    ->schema([
                                        Flex::make([
                                            TextEntry::make('name')
                                                ->grow(false)
                                                ->hiddenLabel()
                                                ->weight(FontWeight::ExtraBold),
                                            TextEntry::make('program_category_id')
                                                ->grow(true)
                                                ->hiddenLabel()
                                                ->formatStateUsing(function ($state) {
                                                    static $categories;
                                                    if (! isset($categories)) {
                                                        $categories = ProgramCategory::pluck('name', 'id');
                                                    }

                                                    return '( ' . ($categories[$state] ?? '') . ' )';
                                                }),
                                            TextEntry::make('contract_budget')
                                                ->grow(false)
                                                ->aboveContent('Nilai Kontrak Program')
                                                ->belowContent(function ($state, Get $get) {
                                                    // Get the current item's data
                                                    // dd($state, $get('planned_budget'));
                                                    if ($state === 0.0 || (float) $get('planned_budget') === 0.0 || $get('planned_budget') === null) {
                                                        return 'Margin 0%';
                                                    }

                                                    return Schema::end((string) 'Margin ' . round(((((float) ($state) - (float) ($get('planned_budget'))) / (float) ($get('contract_budget'))) * 100), 2) . '%');
                                                })
                                                ->hiddenLabel()
                                                ->money(currency: 'IDR', locale: 'id')
                                                ->alignEnd(),
                                            TextEntry::make('planned_budget')
                                                ->grow(false)
                                                ->aboveContent('Nilai planned Program')
                                                ->belowContent(function ($state, Get $get) {
                                                    // Get the current item's data
                                                    if ($state === 0.0 || (float) $get('contract_budget') === 0.0 || $get('contract_budget') === null) {
                                                        return '0%';
                                                    }

                                                    return Schema::end((string) round((((float) ($state) / (float) ($get('contract_budget'))) * 100), 2) . '%');
                                                })
                                                ->hiddenLabel()
                                                ->money(currency: 'IDR', locale: 'id')
                                                ->alignEnd(),
                                        ])
                                            ->from('md'),
                                        // ViewField::make('program_activity_items_review')
                                        //     ->label('Rincian Item')
                                        //     ->view('filament.components.program-items-review-table')
                                        //     ->viewData(fn(Get $get) => [
                                        //         'items' => $get('program_activity_items_review') ?? [],
                                        //     ]),
                                        RepeatableEntry::make('program_activity_items_review')
                                            ->label('Rincian Item')
                                            ->table([
                                                TableColumn::make('Deskripsi')->width('500px'),
                                                TableColumn::make('Aktivitas')->width('250px'),
                                                TableColumn::make('Qty')->width('150px'),
                                                TableColumn::make('Unit Qty'),
                                                TableColumn::make('Frekuensi'),
                                                TableColumn::make('Nilai Kontrak Item')->width('250px'),
                                                TableColumn::make('Nilai Planned Item')->width('250px'),
                                            ])
                                            ->schema([
                                                TextEntry::make('description'),
                                                TextEntry::make('program_activity_name'),
                                                TextEntry::make('quantity'),
                                                TextEntry::make('unit'),
                                                TextEntry::make('frequency'),
                                                TextEntry::make('total_item_budget')
                                                    ->money(currency: 'IDR', locale: 'id'),
                                                TextEntry::make('total_item_planned_budget')
                                                    ->money(currency: 'IDR', locale: 'id'),
                                            ])
                                            ->contained(false)
                                    ]),
                            ]),
                    ]),
                Action::make('import-client-data')
                    ->label('Import Data (Excel/CSV)')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->schema([
                        FileUpload::make('client_data_file')
                            ->label('File Excel / CSV')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions(fn(Action $action): array => [
                        Action::make('review')
                            ->modalWidth(Width::SevenExtraLarge)
                            ->label('Impor')
                            ->icon(Heroicon::OutlinedDocumentArrowUp)
                            ->fillForm(function (array $mountedActions) {
                                $importActionData = $mountedActions[0]->getRawData();
                                $uploadedFiles = $importActionData['client_data_file'];
                                $clientDataFile = null;

                                foreach ($uploadedFiles as $index => $file) {
                                    // $clientDataFile = storage_path(
                                    //     'app/private/livewire-temp/' . $file->getfile
                                    // );
                                    $clientDataFile = $file;
                                }
                                $importedClientDataArray = Excel::toArray(new CreateClientDataImport, $clientDataFile)[0];

                                $result = [
                                    'client_name' => null,
                                    'client_slug' => null,
                                    'client_pics' => [],
                                    'contract_number' => null,
                                    'contract_year' => null,
                                    'start_period' => null,
                                    'end_period' => null,
                                    'programs' => []
                                ];

                                // State machine to track which section we're in
                                $section = 'unknown';
                                $programsMap = [];

                                foreach ($importedClientDataArray as $row) {
                                    $firstCol = $row[0] ?? null;
                                    $secondCol = $row[1] ?? null;

                                    // Detect section transitions
                                    if ($firstCol === "Nama Klien") {
                                        $section = 'client_header';
                                        continue;
                                    }

                                    if ($firstCol === "Jabatan PIC") {
                                        $section = 'pic_header';
                                        continue;
                                    }

                                    if ($firstCol === "Nomor Kontrak") {
                                        $section = 'contract_header';
                                        continue;
                                    }

                                    if ($firstCol === "No" && $secondCol === "Program") {
                                        $section = 'program_header';
                                        continue;
                                    }

                                    // Skip null rows
                                    if ($firstCol === null) {
                                        continue;
                                    }

                                    // Process data based on current section
                                    switch ($section) {
                                        case 'client_header':
                                            $result['client_name'] = $firstCol;
                                            $result['client_slug'] = $secondCol;
                                            $section = 'client_done'; // Prevent re-reading
                                            break;

                                        case 'pic_header':
                                            // Only add if it's not another header
                                            if ($firstCol !== "Nomor Kontrak") {
                                                $result['client_pics'][] = [
                                                    'pic_position' => $firstCol,
                                                    'pic_name' => $secondCol,
                                                    'pic_email' => $row[2] ?? null,
                                                    'pic_phone' => $row[3] ?? null,
                                                ];
                                            }
                                            break;

                                        case 'contract_header':
                                            $result['contract_number'] = $firstCol;
                                            $result['contract_year'] = $secondCol;
                                            $result['start_period'] = isset($row[2]) ? Date::excelToDateTimeObject($row[2])->format('Y-m-d') : null;
                                            $result['end_period'] = isset($row[3]) ? Date::excelToDateTimeObject($row[3])->format('Y-m-d') : null;
                                            $section = 'contract_done'; // Prevent re-reading
                                            break;

                                        case 'program_header':
                                            $programName = $secondCol;
                                            $activityName = $row[2] ?? null;

                                            // Skip if missing critical data
                                            if ($programName === null || $activityName === null) {
                                                break;
                                            }

                                            // Initialize program if not exists
                                            if (!isset($programsMap[$programName])) {
                                                $programsMap[$programName] = [
                                                    'program_name' => $programName,
                                                    'program_category_id' => 1,
                                                    'program_activities' => []
                                                ];
                                            }

                                            // Initialize activity if not exists
                                            if (!isset($programsMap[$programName]['program_activities'][$activityName])) {
                                                $programsMap[$programName]['program_activities'][$activityName] = [
                                                    'activity_name' => $activityName,
                                                    'est_start_date' => now('Asia/Jakarta')->addYear(),
                                                    'est_end_date' => now('Asia/Jakarta')->addYears(2),
                                                    'items' => []
                                                ];
                                            }

                                            // Add item
                                            $programsMap[$programName]['program_activities'][$activityName]['items'][] = [
                                                'description' => $row[3] ?? null,
                                                'qty' => $row[4] ?? null,
                                                'unit_qty' => $row[5] ?? null,
                                                'freq' => $row[6] ?? null,
                                                'contract_unit_price' => $row[7] ?? null,
                                                'contract_total_price' => $row[8] ?? null,
                                                'planned_unit_price' => $row[9] ?? null,
                                                'planned_total_price' => $row[10] ?? null,
                                            ];
                                            break;
                                    }
                                }

                                // Convert programs map to array
                                foreach ($programsMap as $program) {
                                    $program['program_activities'] = array_values($program['program_activities']);
                                    $result['programs'][] = $program;
                                }

                                return $result;
                            })
                            ->steps([
                                Step::make('Detail Klien dan Kontrak Kerja')
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Fieldset::make('Detail Klien')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('client_name')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->label('Nama Klien')
                                                            ->skipRenderAfterStateUpdated()
                                                            ->trim(),

                                                        TextInput::make('client_slug')
                                                            ->required()
                                                            ->label('Slug')
                                                            ->trim()
                                                            ->unique(ignoreRecord: true, column: 'code')
                                                            // ->live(debounce: 700)
                                                            ->maxLength(50)
                                                            ->placeholder('e.g., DMF')
                                                            ->helperText('Kode Unik Klien sebagai referensi'),
                                                    ]),
                                                Fieldset::make('Informasi Kontrak Kerja')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('contract_number')
                                                            ->label('Nomor Kontrak')
                                                            ->trim(),
                                                        TextInput::make('contract_year')
                                                            ->required()
                                                            ->label('Tahun Kontrak')
                                                            ->numeric()
                                                            // ->live(debounce: 700)
                                                            ->minValue(2020)
                                                            ->maxValue(2050)
                                                            ->default(now()->year),

                                                        Grid::make()
                                                            ->schema([
                                                                DatePicker::make('start_period')
                                                                    ->required()
                                                                    ->native(false)
                                                                    ->label('Awal Periode')
                                                                    ->default(today()->toDateString())
                                                                    ->displayFormat('j M Y'),

                                                                DatePicker::make('end_period')
                                                                    ->required()
                                                                    ->native(false)
                                                                    ->label('Akhir Periode')
                                                                    ->displayFormat('j M Y')
                                                                    ->default(today()->addYear()->toDateString())
                                                                    ->after('start_date'),
                                                            ]),
                                                    ]),
                                                Fieldset::make('PIC Klien')
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        Repeater::make('client_pics')
                                                            ->hiddenLabel()
                                                            ->table(
                                                                [
                                                                    TableColumn::make('Jabatan'),
                                                                    TableColumn::make('Nama PIC'),
                                                                    TableColumn::make('Email PIC'),
                                                                    TableColumn::make('No. HP PIC'),
                                                                ]
                                                            )
                                                            ->schema([
                                                                TextInput::make('pic_position')
                                                                    ->placeholder('Jabatan PIC')
                                                                    ->trim(),
                                                                TextInput::make('pic_name')
                                                                    ->required()
                                                                    ->placeholder('Ketikkan nama PIC')
                                                                    ->trim(),
                                                                TextInput::make('pic_email')
                                                                    ->placeholder('pic@mail.com')
                                                                    ->trim()
                                                                    ->email(),
                                                                TextInput::make('pic_phone')
                                                                    ->required()
                                                                    ->placeholder('081234567890')
                                                                    ->trim()
                                                                    ->tel(),
                                                            ])
                                                            ->columnSpanFull()
                                                            ->defaultItems(0)
                                                            ->reorderable(true),
                                                    ]),
                                                SpatieMediaLibraryFileUpload::make('documents')
                                                    ->label('Dokumen')
                                                    ->acceptedFileTypes([
                                                        'image/png',
                                                        'image/jpeg',
                                                        'application/pdf',
                                                        'application/msword',
                                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                        'application/vnd.ms-excel',
                                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                    ])
                                                    ->multiple()
                                                    ->appendFiles()
                                                    ->maxSize(10240)
                                                    ->storeFiles(false)
                                                    ->maxFiles(10)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                                Step::make('Daftar Program')
                                    ->schema([
                                        Repeater::make('programs')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->columns(4)
                                            ->defaultItems(0)
                                            ->collapsed(true)
                                            ->itemLabel(fn(array $state): ?string => $state['program_name'] ?? null)
                                            ->schema([
                                                Select::make('program_category_id')
                                                    ->required()
                                                    ->options(ProgramCategory::query()->pluck('name', 'id'))
                                                    ->default(1)
                                                    ->selectablePlaceholder(false)
                                                    ->columnSpan(2),
                                                TextInput::make('program_name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->trim()
                                                    ->columnSpan(2),
                                                TextInput::make('description')
                                                    ->required()
                                                    ->trim()
                                                    ->placeholder('Deskripsi program')
                                                    ->columnSpan(2),
                                                Select::make('program_pic')
                                                    ->required()
                                                    ->options(function () {
                                                        static $options;
                                                        if (! isset($options)) {
                                                            $options = Employee::query()
                                                                ->select('employees.id', 'users.name')
                                                                ->join('users', 'employees.user_id', '=', 'users.id')
                                                                ->join('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
                                                                ->join('departments', 'job_titles.department_id', '=', 'departments.id')
                                                                ->where('departments.code', 'PROG')
                                                                ->pluck('users.name', 'employees.id');
                                                        }

                                                        return $options;
                                                    })
                                                    ->columnSpan(2),
                                                Repeater::make('program_activities')
                                                    ->label('Aktivitas Program')
                                                    ->defaultItems(0)
                                                    ->columnSpanFull()
                                                    ->columns(4)
                                                    ->collapsed(true)
                                                    ->itemLabel(fn(array $state): ?string => $state['activity_name'] ?? null)
                                                    ->schema([
                                                        TextInput::make('activity_name')
                                                            ->required()
                                                            ->live(onBlur: true),
                                                        DatePicker::make('est_start_date')
                                                            ->required()
                                                            ->native(false)
                                                            ->default(now())
                                                            ->displayFormat('j M Y'),
                                                        DatePicker::make('est_end_date')
                                                            ->required()
                                                            ->native(false)
                                                            ->displayFormat('j M Y')
                                                            ->default(now()->addYear())
                                                            ->after('est_start_date'),
                                                        Repeater::make('items')
                                                            ->label('Item Aktivitas')
                                                            ->defaultItems(0)
                                                            ->columnSpanFull()
                                                            ->extraAttributes([
                                                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]'
                                                            ])
                                                            ->compact()
                                                            ->table([
                                                                TableColumn::make('Detail Item')->width('280px'),
                                                                TableColumn::make('Qty')->width('280px'),
                                                                TableColumn::make('Unit Qty')->width('280px'),
                                                                TableColumn::make('Frekuensi')->width('280px'),
                                                                TableColumn::make('Harga Satuan (Kontrak)')->width('280px'),
                                                                TableColumn::make('Harga Total (Kontrak)')->width('280px'),
                                                                TableColumn::make('Harga Satuan (Planned)')->width('280px'),
                                                                TableColumn::make('Harga Total (Planned)')->width('280px'),
                                                            ])
                                                            ->schema([
                                                                TextInput::make('description'),
                                                                TextInput::make('qty'),
                                                                TextInput::make('unit_qty'),
                                                                TextInput::make('freq'),
                                                                TextInput::make('contract_unit_price'),
                                                                TextInput::make('contract_total_price'),
                                                                TextInput::make('planned_unit_price'),
                                                                TextInput::make('planned_total_price'),
                                                            ])
                                                    ])

                                            ]),
                                    ]),
                            ])
                            ->action(function (array $data) {
                                try {
                                    DB::transaction(function () use ($data) {
                                        $createdClient = Client::create([
                                            'code' => $data['client_slug'],
                                            'name' => $data['client_name'],
                                        ]);

                                        foreach ($data['documents'] as $index => $document) {
                                            $createdClient->addMedia($document)->toMediaCollection('client_documents');
                                        }

                                        $createdContractData = PartnershipContract::create([
                                            'client_id' => $createdClient->id,
                                            'contract_number' => $data['contract_number'],
                                            'contract_year' => $data['contract_year'],
                                            'start_date' => $data['start_period'],
                                            'end_date' => $data['end_period']
                                        ]);

                                        foreach ($data['client_pics'] as $index => $pic) {
                                            ClientPic::create([
                                                'client_id' => $createdClient->id,
                                                'name' => $pic['pic_name'],
                                                'email' => $pic['pic_email'],
                                                'phone' => $pic['pic_phone'],
                                                'position' => $pic['pic_position']
                                            ]);
                                        }

                                        foreach ($data['programs'] as $index => $program) {
                                            $createdProgram = Program::create([
                                                'code' => Str::slug($data['client_slug'] . ' ' . $program['program_name']),
                                                'program_category_id' => $program['program_category_id'],
                                                'employee_id' => $program['program_pic'],
                                                'description' => $program['description'],
                                                'name' => $program['program_name']
                                            ]);

                                            $createdContractData->programs()->attach($createdProgram->id);

                                            $createdCoa = Coa::create([
                                                'code' => Str::slug($data['contract_year'] . ' ' . $createdProgram->code),
                                                'name' => implode(' - ', [$data['contract_year'], $data['client_slug'], $program['program_name']]),
                                                'type' => COAType::Program,
                                                'partnership_contract_id' => $createdContractData->id,
                                                'program_id' => $createdProgram->id,
                                            ]);

                                            foreach ($program['program_activities'] as $actIndex => $activity) {
                                                $createdActivity = ProgramActivity::create([
                                                    'code' => Str::slug($createdCoa->code . ' ' . $activity['activity_name']),
                                                    'name' => $activity['activity_name'],
                                                    'coa_id' => $createdCoa->id,
                                                    'est_start_date' => $activity['est_start_date'],
                                                    'est_end_date' => $activity['est_end_date']
                                                ]);

                                                foreach ($activity['items'] as $itemIndex => $item) {
                                                    ProgramActivityItem::create([
                                                        'program_activity_id' => $createdActivity->id,
                                                        'description' => $item['description'],
                                                        'volume' => $item['qty'],
                                                        'unit' => $item['unit_qty'],
                                                        'frequency' => $item['freq'],
                                                        'total_item_budget' => $item['contract_total_price'],
                                                        'total_item_planned_budget' => $item['planned_total_price']
                                                    ]);
                                                }
                                            }
                                        }
                                        Log::info("\n\n\nClient Created : " . $data['client_slug'] . "\n\n\n");

                                        Notification::make('client-created')
                                            ->title('Klien Berhasil Didaftarkan!')
                                            ->success()
                                            ->send();
                                    });
                                } catch (\Throwable $th) {
                                    Log::error("\n\n\n" . $th->getMessage() . "\n\n\n");
                                    Notification::make('client-created')
                                        ->title('Klien Gagal Didaftarkan!')
                                        ->body('Mohon coba kembali setelah beberapa saat.')
                                        ->danger()
                                        ->send();
                                }
                            })
                    ])
            ])
                ->label('Daftarkan Klien Baru')
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->button()
        ];
    }
}


// [
//     "client_name" => CLIENT_NAME,
//     "client_slug" => CLIENT_SLUG,
//     "client_pics" => [
//         [
//             "pic_position" => PIC_POSITION,
//             "pic_name" => PIC_NAME,
//             "pic_email" => PIC_EMAIL,
//             "pic_phone" => PIC_PHONE
//         ]
//     ],
//     "contract_number" => CONTRACT_NUMBER,
//     "contract_year" => CONTRACT_YEAR,
//     "start_period" => START_PERIOD,
//     "end_period" => END_PERIOD,
//     "programs" => [
//         [
//             "program_name" => PROGRAM_NAME,
//             "program_activities" => [
//                 "activity_name" => ACTIVITY_NAME,
//                 "items" => [
//                     'description' => DETAIL_ITEMS,
//                     'qty' => QTY,
//                     'unit_qty' => UNIT_QUANTITY,
//                     'freq' => Freq,
//                     'contract_unit_price' => Contract Unit Price,
//                     'contract_total_price' => Contract Total,
//                     'planned_unit_price' => Planned Unit Price,
//                     'planned_total_price' => Planned Total
//                 ]
//             ]
//         ]
//     ]
// ]
