<?php

namespace App\Filament\Escola\Pages;

use App\Filament\Widgets\CandidateStatusChart;
use App\Filament\Widgets\CandidatesByProvinceChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StudentsByCourseChart;
use App\Filament\Widgets\StudentStatusChart;
use App\Models\Course;
use App\Models\Institution;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'fas-tachometer-alt';

    protected static ?string $navigationLabel = 'Painel de Controlo';

    protected static ?string $title = 'Painel de Controlo';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('AccessPanel:Escola') || $user?->can('View:Dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return 'Painel de Controlo';
    }

    public function mountHasFilters(): void
    {
        $filtersSessionKey = $this->getFiltersSessionKey();

        if (! count($this->filters ?? [])) {
            $this->filters = null;
        }

        if (
            ($this->filters === null) &&
            $this->persistsFiltersInSession() &&
            session()->has($filtersSessionKey)
        ) {
            $this->filters = session()->get($filtersSessionKey);
        }

        if ($this->filters) {
            $this->normalizeTableFilterValuesFromQueryString($this->filters);
        }

        if ($tenantId = Filament::getTenant()?->id) {
            $this->filters = array_replace($this->filters ?? [], [
                'institution_id' => $tenantId,
            ]);

            if (
                filled($this->filters['course_id'] ?? null) &&
                ! Course::query()
                    ->whereKey($this->filters['course_id'])
                    ->where('institution_id', $tenantId)
                    ->exists()
            ) {
                $this->filters['course_id'] = null;
            }
        }

        $this->getFiltersForm()->fill($this->filters);

        if ($this->persistsFiltersInSession()) {
            session()->put($filtersSessionKey, $this->filters);
        }
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
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
                            ->placeholder('Instituição actual')
                            ->options(fn () => Filament::getTenant()
                                ? collect([Filament::getTenant()->id => Filament::getTenant()->name])
                                : Institution::query()->orderBy('name')->pluck('name', 'id'))
                            ->default(fn (): ?int => Filament::getTenant()?->id)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('course_id', null)),

                        Select::make('course_id')
                            ->label('Curso')
                            ->placeholder('Todos os Cursos')
                            ->options(fn (callable $get) => Course::query()
                                ->when(
                                    Filament::getTenant()?->id ?? $get('institution_id'),
                                    fn ($query, $institutionId) => $query->where('institution_id', $institutionId)
                                )
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

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            CandidatesByProvinceChart::class,
            CandidateStatusChart::class,
            StudentStatusChart::class,
            StudentsByCourseChart::class,
        ];
    }
}
