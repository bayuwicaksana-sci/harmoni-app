<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\JobLevel;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DepartmentCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Department')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Department')
                            ->required()
                            ->trim()
                            ->maxLength(100),

                        TextInput::make('code')
                            ->label('Kode Department')
                            ->required()
                            ->trim()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., BOD, PROG, FIN, HRGA'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Daftar Job Title')
                    ->schema([
                        Repeater::make('job_titles')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('title')
                                    ->trim()
                                    ->live(debounce: 500)
                                    ->partiallyRenderComponentsAfterStateUpdated(['job_level_id'])
                                    ->dehydrated(fn($state) => $state !== null)
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state === null) {
                                            $set('job_level_id', null);
                                        }
                                    }),
                                Select::make('job_level_id')
                                    ->disabled(fn(Get $get) => $get('title') === null)
                                    ->dehydrated(fn($state) => $state !== null)
                                    ->required(fn(Get $get) => $get('title') !== null)
                                    ->options(JobLevel::query()->pluck('name', 'id'))
                            ])
                            ->minItems(1)
                            ->defaultItems(2)
                            ->grid([
                                'default' => 1,
                                'md' => 2
                            ])
                        // ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        //     $splittedWords = preg_split("/[\s,_-]+/", $data['title']);

                        //     $code = "";
                        //     foreach ($splittedWords as $w) {
                        //         $code .= ucfirst(mb_substr($w, 0, 1));
                        //     }
                        //     $data['code'] = $code;

                        //     return $data;
                        // })
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
