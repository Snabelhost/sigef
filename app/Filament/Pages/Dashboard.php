<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Institution;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'fas-tachometer-alt';

    protected static ?string $navigationLabel = 'Painel de Controlo';

    protected static ?string $title = 'Painel de Controlo';

    public static function getNavigationLabel(): string
    {
        return 'Painel de Controlo';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }

    public function getFiltersFormContentComponent(): Component
    {
        return EmbeddedSchema::make('filtersForm')
            ->columnSpanFull()
            ->extraAttributes(['class' => 'sigef-dashboard-filters']);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'sigef-dashboard-filters-form'])
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
                'xl' => 1,
                '2xl' => 1,
            ])
            ->components([
                Section::make('Filtros dos gráficos')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'sigef-dashboard-filters-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('institution_id')
                            ->label('Instituição de Ensino')
                            ->placeholder('Todas as Instituições')
                            ->options(fn() => Institution::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(callable $set) => $set('course_id', null)),

                        Select::make('course_id')
                            ->label('Curso')
                            ->placeholder('Todos os Cursos')
                            ->options(fn(callable $get) => Course::query()
                                ->when($get('institution_id'), fn($query, $institutionId) => $query->where('institution_id', $institutionId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false),

                        DatePicker::make('start_date')
                            ->label('Data Início')
                            ->placeholder('dd/mm/yyyy')
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        DatePicker::make('end_date')
                            ->label('Data Fim')
                            ->placeholder('dd/mm/yyyy')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ]),
            ]);
    }
}
