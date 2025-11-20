<?php

namespace App\Filament\Resources\Clients\Pages;

use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use App\Enums\COAType;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Schemas\ClientCreateForm;
use App\Filament\Resources\Clients\Schemas\ClientCreateForm2;
use App\Models\Client;
use App\Models\Coa;
use App\Models\PartnershipContract;
use App\Models\Program;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\ProgramCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateClient extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ClientResource::class;
    protected ?array $partnershipContractData;
    protected ?array $programsData;
    protected ?Client $createdClient;

    // public function form(Schema $schema): Schema
    // {
    //     return ClientCreateForm2::configure($schema);
    // }

    protected function getSteps(): array
    {
        return [
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
                                        ->helperText('Kode Unik Klien sebagai referensi')
                                ]),
                            Fieldset::make('Informasi Kontrak Kerja')
                                ->columns(1)
                                ->schema([
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
                                                ->default(now())
                                                ->displayFormat('d/m/Y'),

                                            DatePicker::make('end_date')
                                                // ->required()
                                                ->native(false)
                                                ->label('Akhir Periode')
                                                ->displayFormat('d/m/Y')
                                                ->default(now()->addYear())
                                                ->after('start_date')
                                        ]),
                                ]),
                        ])
                ]),
            Step::make('Daftar Program')
                ->schema([
                    Repeater::make('programs')
                        ->hiddenLabel()
                        ->live(debounce: 700)
                        ->table([
                            TableColumn::make('Nama Program'),
                            TableColumn::make('Kategori Program'),
                        ])
                        ->compact()
                        ->schema([
                            TextInput::make('name')
                                ->trim()
                                ->placeholder('Ketikan nama program')
                                ->live(debounce: 700),
                            // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                            // ->dehydrated(fn($state) => null !== $state),
                            Select::make('program_category_id')
                                // ->disabled(fn(Get $get) => null === $get('name'))
                                // ->required(fn(Get $get) => null !== $get('name'))
                                ->options(ProgramCategory::query()->pluck('name', 'id'))
                                ->default(1)
                                ->selectablePlaceholder(false)
                        ])
                        ->defaultItems(5)
                        ->columns(2)
                        ->reorderable(false)
                ]),
            Step::make('Daftar Aktivitas Program')
                ->schema([
                    Repeater::make('program_activities')
                        ->hiddenLabel()
                        ->reorderable()
                        ->defaultItems(5)
                        ->table([
                            TableColumn::make('Nama Aktivitas'),
                            TableColumn::make('Program'),
                        ])
                        ->compact()
                        ->live(debounce: 700)
                        ->schema([
                            TextInput::make('name')
                                ->trim()
                                ->placeholder('Ketikan nama aktivitas')
                                ->live(debounce: 700),
                            // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                            // ->dehydrated(fn($state) => null !== $state),
                            Select::make('program_code')
                                // ->disabled(fn(Get $get) => null === $get('name'))
                                // ->required(fn(Get $get) => null !== $get('name'))
                                ->live(debounce: 700)
                                ->options(function (Get $get) {
                                    $programs = $get('../../programs');
                                    $options = [];
                                    foreach ($programs as $program) {
                                        $programCode = implode('-', [$get('../../code'), str_replace(' ', '-', $program['name'])]);
                                        if (!empty($program['name'])) {
                                            $options[$programCode] = $program['name'];
                                        }
                                    }

                                    return $options;
                                })
                        ])
                        ->columns(2)
                        ->reorderable(false)
                ]),
            Step::make('Rincian Item Aktivitas')
                ->schema([
                    Repeater::make('program_activity_items')
                        ->hiddenLabel()
                        ->live(debounce: 700)
                        ->reorderable()
                        ->defaultItems(10)
                        ->table([
                            TableColumn::make('Deskripsi')->width('200px'),
                            TableColumn::make('Aktivitas')->width('150px'),
                            TableColumn::make('Kuantitas'),
                            TableColumn::make('Satuan'),
                            TableColumn::make('Frekuensi'),
                            TableColumn::make('Nilai Kontrak Item')->width('300px'),
                            TableColumn::make('Nilai Planned Item')->width('300px'),
                        ])
                        ->compact()
                        ->schema([
                            TextInput::make('description')
                                ->trim()
                                ->placeholder('Ketikan deskripsi item')
                                ->live(onBlur: true)
                                ->partiallyRenderComponentsAfterStateUpdated(['program_activity_code']),
                            // ->live(onBlur: true)
                            // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                            // ->dehydrated(fn($state) => null !== $state),
                            Select::make('program_activity_code')
                                // ->disabled(fn(Get $get) => null === $get('name'))
                                // ->required(fn(Get $get) => null !== $get('name'))
                                ->options(function (Get $get) {
                                    $activities = $get('../../program_activities');
                                    $options = [];
                                    foreach ($activities as $activity) {
                                        $coaCode = implode("-", [$activity['program_code'], (string)$get('../../contract_year')]);
                                        $activityCode = implode("-", [$coaCode, Str::slug($activity['name'])]);
                                        if (!empty($activity['name'])) {
                                            $options[$activityCode] = $activity['name'];
                                        }
                                    }

                                    return $options;
                                }),
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
                                ->minValue(1),
                            TextInput::make('total_item_planned_budget')
                                ->numeric()
                                ->prefix('Rp')
                                ->minValue(1),
                        ])
                        ->columns(2)
                        ->reorderable(false)
                ]),

            Step::make('Review')
                ->schema([
                    Fieldset::make('Detail Klien dan Kontrak Kerja')
                        ->columns(5)
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
                                ->date('j M y'),
                            TextEntry::make('end_date_review')
                                ->label('Akhir Periode')
                                ->state(fn(Get $get) => $get('end_date'))
                                ->date('j M y'),
                            TextEntry::make('total_contract_value')
                                ->label('Total Nilai Kontrak')
                                ->state(function (Get $get) {
                                    $activityItems = $get('program_activity_items') ?? [];

                                    $totalContractValue = 0;
                                    foreach ($activityItems as $itemIndex => $item) {
                                        $totalContractValue += (float) $item['total_item_budget'];
                                    }

                                    return $totalContractValue;
                                })
                                ->money(currency: 'IDR', locale: 'id')
                        ]),
                    RepeatableEntry::make('programs_review')
                        ->label('Daftar Program')
                        ->state(function (Get $get) {
                            $programs = array_filter($get('programs'), function ($item) {
                                // Check if the 'name' key exists and is not empty
                                return isset($item['name']) && !empty($item['name']);
                            });
                            $programActivities = array_filter($get('program_activities'), function ($item) {
                                // Check if the 'name' key exists and is not empty
                                return isset($item['name']) && !empty($item['name']);
                            });
                            $programActivityItems = array_filter($get('program_activity_items'), function ($item) {
                                // Check if the 'name' key exists and is not empty
                                return isset($item['description']) && !empty($item['description']);
                            });

                            $results = [];

                            // Loop through each program
                            foreach ($programs as $program) {
                                $program['code'] = implode('-', [$get('code'), str_replace(' ', '-', $program['name'])]);
                                $program['program_activity_items_review'] = [];
                                $program['contract_budget'] = 0;
                                $program['planned_budget'] = 0;
                                // $program = [
                                //     'name' => $program['name'],
                                //     'code' => implode('-', [$get('code'), str_replace(' ', '-', $program['name'])]),
                                //     'program_activity_items_review' => []
                                // ];

                                // Find all activities for this program
                                foreach ($programActivities as $activity) {
                                    $coaCode = implode("-", [$activity['program_code'], (string)$get('contract_year')]);
                                    $activityCode = implode("-", [$coaCode, Str::slug($activity['name'])]);
                                    if ($activity['program_code'] === $program['code']) {
                                        // Find all items for this activity
                                        foreach ($programActivityItems as $item) {
                                            if ($item['program_activity_code'] === $activityCode) {
                                                // Add the item with the activity name
                                                $program['contract_budget'] += (float) $item['total_item_budget'];
                                                $program['planned_budget'] += (float) $item['total_item_planned_budget'];
                                                $item['program_activity_name'] = $activity['name'];
                                                $program['program_activity_items_review'][] = $item;
                                            }
                                        }
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
                                    ->formatStateUsing(fn($state) => '( ' . ProgramCategory::find($state)->name . ' )'),
                                TextEntry::make('contract_budget')
                                    ->grow(false)
                                    ->aboveContent('Nilai Kontrak Program')
                                    ->hiddenLabel()
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->alignEnd(),
                                TextEntry::make('planned_budget')
                                    ->grow(false)
                                    ->aboveContent('Nilai planned Program')
                                    ->hiddenLabel()
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->alignEnd()
                            ])
                                ->from('md'),
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
                        ])
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        dd("Hai");
        $this->partnershipContractData = [
            'contract_year' => (int) $data['contract_year'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
        $this->programsData = array_filter($data['programs']);
        // dd($this->programsData);

        unset(
            $data['contract_year'],
            $data['start_date'],
            $data['end_date'],
            $data['programs']
        );

        // dd($data, $this->programsData);

        $totalContractValue = 0.00;

        foreach ($this->programsData as $index => $program) {
            foreach ($program['program_activities'] as $key => $activities) {
                foreach ($activities['program_activity_items'] as $key => $items) {
                    $totalContractValue += (float) $items['total_item_budget'];
                }
            }
        }

        $this->partnershipContractData['contract_value'] = $totalContractValue;

        // dd($data, $this->partnershipContractData, $this->programsData, $totalContractValue);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $this->createdClient = static::getModel()::create($data);
        return $this->createdClient;
    }

    public function afterCreate(): void
    {
        $client = $this->createdClient;

        $contractNumber = implode('-', [$client->code, $this->partnershipContractData['contract_year'], str_pad((string) $client->id, 3, "0", STR_PAD_LEFT)]);

        $this->partnershipContractData['client_id'] = $client->id;

        $this->partnershipContractData['contract_number'] = $contractNumber;

        $createdContract = PartnershipContract::create($this->partnershipContractData);

        foreach ($this->programsData as $index => $program) {
            $programCode = implode('-', [$client->code, str_replace(' ', '-', $program['name'])]);

            $createdProgram = Program::create([
                'code' => $programCode,
                'program_category_id' => $program['program_category_id'],
                'name' => $program['name']
            ]);

            $createdContract->programs()->attach($createdProgram->id);

            $programContractBudget = 0;
            $programPlannedBudget = 0;

            foreach ($program['program_activities'] as $key => $activities) {
                foreach ($activities['program_activity_items'] as $key => $items) {
                    $programContractBudget += (float) $items['total_item_budget'];
                    $programPlannedBudget += (float) $items['total_item_planned_budget'];
                }
            }

            $coaCode = implode("-", [$programCode, (string)$createdContract->contract_year]);

            $createdCoa = Coa::create([
                'code' => $coaCode,
                'name' => $createdProgram->name . ' ' . $createdContract->contract_year,
                'type' => COAType::Program,
                'program_id' => $createdProgram->id,
                'contract_year' => $createdContract->contract_year,
                'budget_amount' => (float) $programContractBudget,
                'planned_budget' => (float) $programPlannedBudget
            ]);

            foreach ($program['program_activities'] as $key => $activities) {
                $activityCode = implode("-", [$coaCode, Str::slug($activities['name'])]);

                $createdProgramActivity = ProgramActivity::create([
                    'code' => $activityCode,
                    'name' => $activities['name'],
                    'coa_id' => $createdCoa->id,
                ]);

                foreach ($activities['program_activity_items'] as $key => $items) {
                    $items['program_activity_id'] = $createdProgramActivity->id;

                    ProgramActivityItem::create($items);
                }
            }
        }
    }
}
