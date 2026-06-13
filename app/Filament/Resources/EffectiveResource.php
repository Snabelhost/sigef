<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EffectiveResource\Pages;
use App\Exports\EffectiveTemplateExport;
use App\Imports\EffectiveImport;
use App\Models\CardTemplate;
use App\Models\Effective;
use App\Models\Institution;
use App\Models\Municipality;
use App\Models\Provenance;
use App\Models\Province;
use App\Models\Rank;
use App\Services\StaffLoginAccountService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class EffectiveResource extends Resource
{
    protected static ?string $model = Effective::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Recursos Humanos';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Efectivos';

    protected static ?string $modelLabel = 'Efectivo';

    protected static ?string $pluralModelLabel = 'Efectivos';

    protected static ?string $slug = 'effectives';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(Filament::getCurrentPanel()?->getId() === 'escola' && Filament::getTenant()?->id, function (Builder $query): Builder {
                return $query->where('institution_id', Filament::getTenant()->id);
            })
            ->with(['institution', 'cardTemplate', 'user']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::effectiveFormSchema());
    }

    protected static function effectiveFormSchema(): array
    {
        return [
            Tabs::make('Ficha do Efectivo')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Dados Profissionais')
                        ->icon('heroicon-o-briefcase')
                        ->schema(static::professionalDataTabSchema()),
                    Tab::make('Carga / Horário')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Section::make('Horário de Trabalho')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('work_shift')
                                        ->label('Turno')
                                        ->options(Effective::workShiftOptions())
                                        ->native(false),
                                    Forms\Components\TimePicker::make('work_start_time')
                                        ->label('Hora de início')
                                        ->seconds(false),
                                    Forms\Components\TimePicker::make('work_end_time')
                                        ->label('Hora de fim')
                                        ->seconds(false),
                                    Forms\Components\TextInput::make('weekly_hours')
                                        ->label('Horas semanais')
                                        ->numeric()
                                        ->suffix('h'),
                                    Forms\Components\CheckboxList::make('work_days')
                                        ->label('Dias de trabalho')
                                        ->options([
                                            'Segunda-feira' => 'Segunda-feira',
                                            'Terça-feira' => 'Terça-feira',
                                            'Quarta-feira' => 'Quarta-feira',
                                            'Quinta-feira' => 'Quinta-feira',
                                            'Sexta-feira' => 'Sexta-feira',
                                            'Sábado' => 'Sábado',
                                            'Domingo' => 'Domingo',
                                        ])
                                        ->columns(3)
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('work_schedule_notes')
                                        ->label('Observações do horário')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Salário')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Informações Salariais')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('salary_base')
                                        ->label('Salário base')
                                        ->numeric()
                                        ->prefix('Kz'),
                                    Forms\Components\TextInput::make('salary_allowances')
                                        ->label('Subsídios')
                                        ->numeric()
                                        ->prefix('Kz'),
                                    Forms\Components\TextInput::make('salary_deductions')
                                        ->label('Descontos')
                                        ->numeric()
                                        ->prefix('Kz'),
                                    Forms\Components\Select::make('salary_currency')
                                        ->label('Moeda')
                                        ->options([
                                            'AOA' => 'AOA',
                                            'USD' => 'USD',
                                            'EUR' => 'EUR',
                                        ])
                                        ->default('AOA')
                                        ->native(false),
                                    Forms\Components\Textarea::make('salary_notes')
                                        ->label('Notas salariais')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Informações Bancárias')
                        ->icon('heroicon-o-building-library')
                        ->schema([
                            Section::make('Informações Bancárias')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('bank_name')
                                        ->label('Banco')
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('bank_account_name')
                                        ->label('Titular da conta')
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('bank_account_number')
                                        ->label('Número da conta')
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('iban')
                                        ->label('IBAN')
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('swift_code')
                                        ->label('SWIFT')
                                        ->maxLength(191),
                                ]),
                        ]),
                    Tab::make('Arquivos')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make('Arquivos')
                                ->columns(3)
                                ->schema([
                                    static::documentUpload('file_identity_card', 'Bilhete de Identidade'),
                                    static::documentUpload('file_contract', 'Contrato / Nomeação'),
                                    static::documentUpload('file_cv', 'Curriculum Vitae'),
                                    static::documentUpload('file_certificate', 'Certificado'),
                                    static::documentUpload('file_other_document', 'Outro documento'),
                                ]),
                        ]),
                ]),
        ];
    }

    protected static function professionalDataTabSchema(): array
    {
        return [
            Section::make('Dados Profissionais')
                ->schema([
                    Html::make(static::effectivePhotoUploadStyles()),

                    Grid::make([
                        'default' => 1,
                        'lg' => 12,
                    ])->schema([
                        Group::make([
                            Forms\Components\FileUpload::make('photo')
                                ->label('Foto')
                                ->hiddenLabel()
                                ->image()
                                ->disk('public')
                                ->directory('effectives/photos')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/*'])
                                ->extraInputAttributes([
                                    'accept' => 'image/*',
                                    'data-sigef-photo-input' => 'true',
                                ])
                                ->extraAttributes([
                                    'class' => 'sigef-trainer-photo-upload',
                                    'data-sigef-photo-upload' => 'effective',
                                ])
                                ->imageEditor()
                                ->imagePreviewHeight('10rem')
                                ->panelAspectRatio('1:1')
                                ->panelLayout('integrated')
                                ->placeholder(static::effectivePhotoUploadPlaceholder())
                                ->maxSize(4096),
                            Html::make(static::effectivePhotoUploadActions())
                                ->hiddenOn('view'),
                            Html::make(fn (?Effective $record): HtmlString => static::effectivePhotoPreviewTrigger($record))
                                ->visibleOn('view'),
                        ])
                            ->extraAttributes([
                                'class' => 'sigef-trainer-photo-view-group',
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            Forms\Components\ToggleButtons::make('staff_type')
                                ->label('Tipo de Efectivo')
                                ->options(Effective::staffTypeOptions())
                                ->icons([
                                    'regime_especial' => 'heroicon-o-shield-check',
                                    'regime_geral' => 'heroicon-o-user',
                                ])
                                ->extraAttributes([
                                    'class' => 'sigef-trainer-type-toggle',
                                ])
                                ->default('regime_especial')
                                ->inline()
                                ->grouped()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    if ($state === 'regime_especial') {
                                        $set('identity_document', null);
                                        $set('category', null);
                                        $set('nas', null);
                                    }

                                    if ($state === 'regime_geral') {
                                        $set('employee_number', null);
                                        $set('position', null);
                                        $set('placement_organ', null);
                                    }
                                })
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('employee_number')
                                ->label('NIP')
                                ->placeholder('Ex: 1057728')
                                ->unique(ignoreRecord: true)
                                ->maxLength(191)
                                ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_especial') === 'regime_especial'),
                            Forms\Components\TextInput::make('identity_document')
                                ->label('Bilhete de Identidade')
                                ->placeholder('Ex: 007397943LA048')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    static::fillEffectiveDataFromIdentityCard($state, $set);
                                })
                                ->mutateStateForValidationUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                                ->dehydrateStateUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                                ->unique(ignoreRecord: true)
                                ->maxLength(191)
                                ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_especial') === 'regime_geral'),
                            Forms\Components\TextInput::make('full_name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(191),
                        ])->columnSpan([
                            'default' => 1,
                            'lg' => 9,
                        ]),
                    ]),
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
                        Forms\Components\Select::make('gender')
                            ->label('Sexo')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ])
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('blood_type')
                            ->label('Grupo Sanguíneo')
                            ->options(Effective::bloodTypeOptions())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('country')
                            ->label('País de Origem')
                            ->options(static::countryOptions())
                            ->default('Angola')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('province')
                            ->label('Província')
                            ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'name')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('municipality', null)),
                        Forms\Components\Select::make('municipality')
                            ->label('Município')
                            ->options(fn (Get $get): array => static::municipalityOptions($get('province')))
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Data de nascimento')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('position')
                            ->label('Patente')
                            ->options(fn (): array => Rank::query()->orderBy('name')->pluck('name', 'name')->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_especial') === 'regime_especial'),
                        Forms\Components\Select::make('education_level')
                            ->label('Grau Académico')
                            ->options(static::educationLevelOptions())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('situation')
                            ->label('Situação')
                            ->options([
                                'Efectivo' => 'Efectivo',
                                'Contratado' => 'Contratado',
                                'Convidado' => 'Convidado',
                                'Reformado' => 'Reformado',
                                'Inactivo' => 'Inactivo',
                            ])
                            ->default('Efectivo')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('specialization')
                            ->label('Especialização')
                            ->placeholder('Ex: Recursos Humanos, Administração')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('job_function')
                            ->label('Função')
                            ->placeholder('Ex: Técnico Administrativo')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('department')
                            ->label('Departamento')
                            ->maxLength(191),
                        Forms\Components\Select::make('placement_organ')
                            ->label('Órgão de colocação / proveniência')
                            ->options(fn (): array => Provenance::query()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Provenance $provenance): array => [
                                    $provenance->name => $provenance->acronym ? "{$provenance->name} ({$provenance->acronym})" : $provenance->name,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_especial') === 'regime_especial'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->prefix('+244')
                            ->placeholder('9XX XXX XXX')
                            ->mask('999 999 999')
                            ->maxLength(191),
                        Forms\Components\Select::make('institution_id')
                            ->label('Escola')
                            ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->default(fn (): ?int => Filament::getCurrentPanel()?->getId() === 'escola' ? Filament::getTenant()?->id : null)
                            ->hidden(fn (): bool => Filament::getCurrentPanel()?->getId() === 'escola' && filled(Filament::getTenant()?->id))
                            ->dehydrated()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->placeholder('Opcional - será gerado automaticamente')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('father_name')
                            ->label('Nome do pai')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('mother_name')
                            ->label('Nome da mãe')
                            ->maxLength(191),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Data de Admissão')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
                    Forms\Components\Textarea::make('notes')
                        ->label('Biografia')
                        ->placeholder('Resumo profissional, experiência e áreas de actuação.')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function effectivePhotoUploadPlaceholder(): string
    {
        return '<span class="sigef-photo-idle">'
            . '<span class="sigef-photo-camera" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 4.5 7.6 7H5.5A2.5 2.5 0 0 0 3 9.5v8A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7h-2.1L15 4.5H9Zm3 13a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Zm0-2a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg></span>'
            . '</span>';
    }

    protected static function effectivePhotoUploadActions(): HtmlString
    {
        return new HtmlString(
            '<div class="sigef-photo-actions" data-sigef-photo-actions="effective">'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-primary" data-sigef-photo-action="capture"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-9A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7H17l-2.5-3Z"/><circle cx="12" cy="13" r="3"/></svg><span>Capturar</span></button>'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-secondary" data-sigef-photo-action="upload"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg><span>Carregar</span></button>'
            . '</div>'
        );
    }

    protected static function effectivePhotoPreviewTrigger(?Effective $record): HtmlString
    {
        $photoUrl = static::effectivePhotoUrl($record);

        if ($photoUrl === null) {
            return new HtmlString('');
        }

        $name = trim((string) ($record?->full_name ?: 'Efectivo'));

        return new HtmlString(
            '<button type="button" class="sigef-photo-preview-trigger"'
            . ' data-sigef-photo-preview="true"'
            . ' data-sigef-photo-url="' . e($photoUrl) . '"'
            . ' data-sigef-photo-name="' . e($name) . '"'
            . ' aria-label="Visualizar foto de ' . e($name) . '">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>'
            . '</button>'
        );
    }

    protected static function effectivePhotoUrl(?Effective $record): ?string
    {
        $photo = trim((string) ($record?->photo ?? ''));

        if ($photo === '') {
            return null;
        }

        if (Str::startsWith($photo, ['http://', 'https://', 'data:'])) {
            return $photo;
        }

        return asset('storage/' . ltrim($photo, '/'));
    }

    protected static function effectiveAvatarUrl(?Effective $record): string
    {
        $name = trim((string) ($record?->full_name ?: 'Efectivo'));
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? 'E';
        $last = $parts[count($parts) - 1] ?? '';
        $initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));

        if (mb_strlen($initials) < 2) {
            $initials = mb_strtoupper(mb_substr($name, 0, 2));
        }

        $safeInitials = htmlspecialchars($initials ?: 'EF', ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <rect width="96" height="96" rx="48" fill="#041B4E"/>
    <text x="48" y="56" text-anchor="middle" font-family="Arial, sans-serif" font-size="32" font-weight="700" fill="#FFFFFF">{$safeInitials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    protected static function effectivePhotoUploadStyles(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<style>
    .sigef-trainer-photo-upload {
        width: 10.25rem;
        max-width: 10.25rem;
    }

    .sigef-trainer-photo-upload .filepond--root {
        width: 10rem !important;
        height: 10rem !important;
        min-height: 10rem !important;
        margin-bottom: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        overflow: visible;
    }

    .sigef-trainer-photo-upload .filepond--drop-label {
        display: grid !important;
        inset: 0 !important;
        height: 10rem !important;
        min-height: 10rem;
        align-items: center !important;
        justify-content: center !important;
        justify-items: center !important;
        place-items: center !important;
        color: inherit;
        cursor: pointer;
        margin: 0 !important;
        overflow: visible;
        transform: none !important;
    }

    .sigef-trainer-photo-upload .filepond--drop-label label {
        display: flex !important;
        width: 100%;
        height: 100%;
        align-items: center !important;
        justify-content: center !important;
        place-items: center !important;
        overflow: visible;
        padding: 0 !important;
        margin: 0 !important;
        transform: none !important;
    }

    .sigef-trainer-photo-upload .filepond--panel-root {
        background: #f6f8fb;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.55);
        height: 10rem !important;
        min-height: 10rem;
    }

    .sigef-trainer-photo-upload .filepond--panel {
        height: 10rem !important;
        overflow: hidden;
        border-radius: 0.5rem;
    }

    .sigef-trainer-photo-upload .filepond--label-action {
        text-decoration: none;
    }

    .sigef-photo-idle {
        position: absolute;
        inset: 0;
        display: grid;
        width: 100%;
        height: 100%;
        min-height: 100%;
        align-items: center;
        justify-items: center;
        place-items: center;
        line-height: 1;
        margin: auto;
        transform: none;
    }

    .sigef-photo-camera {
        display: grid;
        width: 3.75rem;
        height: 3.75rem;
        place-items: center;
        color: #dce3ed;
        margin: auto;
    }

    .sigef-photo-camera svg {
        width: 100%;
        height: 100%;
    }

    .sigef-photo-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.75rem;
        white-space: nowrap;
    }

    .sigef-photo-action {
        appearance: none;
        border: 0;
        cursor: pointer;
        display: inline-flex;
        height: 2rem;
        min-width: 6.25rem;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border-radius: 0.5rem;
        padding: 0 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 1px 1px rgba(15, 23, 42, 0.04);
    }

    .sigef-photo-action svg {
        width: 0.875rem;
        height: 0.875rem;
    }

    .sigef-photo-action-primary {
        background: #061b42;
        color: #ffffff;
    }

    .sigef-photo-action-secondary {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #132544;
    }

    .sigef-photo-action:hover,
    .sigef-photo-action:focus-visible {
        outline: 2px solid rgba(6, 27, 66, 0.18);
        outline-offset: 2px;
    }

    .sigef-trainer-type-toggle .fi-btn:hover,
    .sigef-trainer-type-toggle .fi-btn:focus-visible,
    .sigef-trainer-type-toggle .fi-fo-toggle-buttons-input:checked + .fi-btn {
        background-color: #061b42 !important;
        color: #ffffff !important;
        --text: #ffffff;
        --hover-text: #ffffff;
        --dark-text: #ffffff;
        --dark-hover-text: #ffffff;
    }

    .sigef-trainer-type-toggle .fi-btn:hover .fi-icon,
    .sigef-trainer-type-toggle .fi-btn:focus-visible .fi-icon,
    .sigef-trainer-type-toggle .fi-fo-toggle-buttons-input:checked + .fi-btn .fi-icon {
        color: #ffffff !important;
    }

    .sigef-photo-capture-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
    }

    .sigef-photo-capture-modal.is-open {
        display: block;
    }

    .sigef-photo-capture-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.62);
    }

    .sigef-photo-capture-dialog {
        position: relative;
        width: min(840px, calc(100vw - 2rem));
        margin: 4vh auto;
        overflow: hidden;
        border: 1px solid #d8e2f1;
        border-radius: 0.875rem;
        background: #ffffff;
        box-shadow: 0 24px 44px rgba(15, 23, 42, 0.24);
    }

    .sigef-photo-capture-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: #061b42;
        color: #ffffff;
        padding: 0.875rem 1rem;
    }

    .sigef-photo-capture-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .sigef-photo-capture-close {
        display: grid;
        width: 2rem;
        height: 2rem;
        place-items: center;
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        cursor: pointer;
    }

    .sigef-photo-capture-close svg {
        width: 1rem;
        height: 1rem;
        fill: currentColor;
    }

    .sigef-photo-capture-body {
        position: relative;
        margin: 1rem;
        min-height: 28rem;
        overflow: hidden;
        border-radius: 0.5rem;
        background: #020817;
    }

    .sigef-photo-capture-body video {
        width: 100%;
        height: min(58vh, 28rem);
        min-height: 28rem;
        object-fit: cover;
        display: block;
    }

    .sigef-photo-capture-grid {
        pointer-events: none;
        position: absolute;
        inset: 50%;
        width: min(22rem, 52vw);
        height: min(22rem, 52vw);
        transform: translate(-50%, -50%);
        border: 2px solid rgba(255, 255, 255, 0.82);
        border-radius: 0.5rem;
        box-shadow: 0 0 0 999px rgba(2, 8, 23, 0.34);
    }

    .sigef-photo-capture-status {
        margin: 0 1rem;
        color: #b91c1c;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .sigef-photo-capture-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.5rem;
        padding: 0 1rem 1rem;
    }

    @media (max-width: 640px) {
        .sigef-photo-capture-dialog {
            margin: 1rem auto;
        }

        .sigef-photo-capture-body,
        .sigef-photo-capture-body video {
            min-height: 20rem;
        }

        .sigef-photo-capture-grid {
            width: min(16rem, 72vw);
            height: min(16rem, 72vw);
        }

        .sigef-photo-capture-actions {
            justify-content: stretch;
            flex-wrap: wrap;
        }
    }
</style>
HTML);
    }

    protected static function documentUpload(string $name, string $label): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory('effectives/documents')
            ->visibility('public')
            ->openable()
            ->downloadable()
            ->maxSize(8192);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(42)
                    ->defaultImageUrl(fn (Effective $record): string => static::effectiveAvatarUrl($record)),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Login')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray')
                    ->placeholder('Sem login')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('identifier')
                    ->label('NIP/NAS/BI')
                    ->placeholder('-')
                    ->searchable(['employee_number', 'nas', 'identity_document']),
                Tables\Columns\TextColumn::make('staff_type')
                    ->label('Regime')
                    ->formatStateUsing(fn (?string $state): string => Effective::staffTypeOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'regime_geral' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('position_label')
                    ->label('Posto')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('department')
                    ->label('Departamento')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('job_function')
                    ->label('Função')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cardTemplate.name')
                    ->label('Cartão')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Padrão'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_type')
                    ->label('Regime')
                    ->options(Effective::staffTypeOptions()),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Escola / Unidade')
                    ->relationship('institution', 'name')
                    ->hidden(fn (): bool => Filament::getCurrentPanel()?->getId() === 'escola' && filled(Filament::getTenant()?->id)),
                Tables\Filters\SelectFilter::make('card_template_id')
                    ->label('Modelo de cartão')
                    ->relationship('cardTemplate', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->headerActions([
                Actions\Action::make('importarExcel')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                    ])
                    ->modalHeading('Importar Efectivos do Excel')
                    ->modalDescription(new HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos efectivos.</span>'))
                    ->modalIcon('heroicon-o-document-arrow-up')
                    ->modalIconColor('primary')
                    ->form([
                        Forms\Components\FileUpload::make('excel_file')
                            ->label('Arquivo Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', '.xlsx', '.xls'])
                            ->directory('temp/imports')
                            ->required()
                            ->helperText('Formatos aceitos: .xlsx, .xls'),
                    ])
                    ->action(function (array $data): void {
                        $filePath = storage_path('app/public/' . $data['excel_file']);

                        if (! file_exists($filePath)) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Arquivo não encontrado.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $import = new EffectiveImport();
                            Excel::import($import, $filePath);

                            $stats = $import->getImportStats();
                            $detailedErrors = $import->getDetailedErrors();

                            @unlink($filePath);

                            if ($stats['imported'] > 0) {
                                Notification::make()
                                    ->title('Importação Concluída')
                                    ->body("Importados: {$stats['imported']} efectivos!")
                                    ->success()
                                    ->send();
                            }

                            if ($stats['skipped'] > 0) {
                                Notification::make()
                                    ->title('Registros Ignorados')
                                    ->body("{$stats['skipped']} já existiam ou estavam incompletos.")
                                    ->warning()
                                    ->send();
                            }

                            if (count($detailedErrors) > 0) {
                                Notification::make()
                                    ->title('Problemas Encontrados')
                                    ->body(implode("\n", array_slice($detailedErrors, 0, 5)))
                                    ->danger()
                                    ->send();
                            }
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Erro na Importação')
                                ->body('Erro: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(fn (Actions\Action $action) => $action->label('Importar')->icon('heroicon-o-arrow-up-tray'))
                    ->modalCancelAction(fn (Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                Actions\Action::make('baixarModelo')
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return Excel::download(new EffectiveTemplateExport(), 'modelo_importacao_efectivos.xlsx');
                    }),
                Actions\CreateAction::make()
                    ->label('Novo Efectivo')
                    ->icon('heroicon-o-plus')
                    ->modalWidth(Width::ScreenExtraLarge)
                    ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn (Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Efectivo criado com sucesso!'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Efectivo')
                        ->modalWidth(Width::ScreenExtraLarge)
                        ->schema(static::effectiveFormSchema())
                        ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    static::sheetAction(),
                    static::previewCardAction(),
                    static::assignLoginPasswordAction(),
                    Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth(Width::ScreenExtraLarge)
                        ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Efectivo atualizado com sucesso!'),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function assignLoginPasswordAction(): Actions\Action
    {
        return Actions\Action::make('assign_login_password')
            ->label('Atribuir senha de login')
            ->icon('heroicon-o-key')
            ->color('success')
            ->modalHeading(fn (Effective $record): string => 'Atribuir senha - '.($record->full_name ?: 'Efectivo'))
            ->modalWidth(Width::Large)
            ->form([
                Forms\Components\TextInput::make('email')
                    ->label('E-mail de login')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->default(fn (Effective $record): ?string => $record->user?->email ?: $record->email)
                    ->helperText('Este e-mail sera usado para entrar no painel da escola.'),
                Forms\Components\TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Conta activa')
                    ->default(fn (Effective $record): bool => (bool) ($record->user?->is_active ?? true)),
            ])
            ->modalSubmitAction(fn (Actions\Action $action) => $action
                ->icon('heroicon-o-check')
                ->label('Guardar senha')
                ->color('primary'))
            ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
            ->action(function (Effective $record, array $data): void {
                try {
                    $user = app(StaffLoginAccountService::class)->assignEffectivePassword(
                        effective: $record,
                        email: (string) ($data['email'] ?? ''),
                        password: (string) ($data['password'] ?? ''),
                        isActive: (bool) ($data['is_active'] ?? true),
                    );
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    throw $exception;
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Erro ao atribuir senha')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Conta de login vinculada')
                    ->body('O efectivo ja pode entrar com '.$user->email.'.')
                    ->success()
                    ->send();
            });
    }

    protected static function sheetAction(): Actions\Action
    {
        return Actions\Action::make('effective_sheet')
            ->label('Ficha do Efectivo')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->modalHeading('Pre-visualizacao da Ficha do Efectivo')
            ->modalDescription(null)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Actions\Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pre-visualizacao')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Effective $record) {
                $printUrl = route('effectives.sheet.print', ['effective' => $record]);
                $effectiveName = trim((string) ($record->full_name ?: 'Efectivo'));
                $identifierLabel = $record->staff_type === 'regime_geral' ? 'N.o DO BI' : 'NIP';
                $identifierNumber = trim((string) (
                    $record->staff_type === 'regime_geral'
                        ? ($record->identity_document ?: $record->document_number ?: $record->nas)
                        : ($record->employee_number ?: $record->document_number)
                ));
                $identifierNumber = $identifierNumber !== '' ? $identifierNumber : '-';
                $frameId = 'sigef-effective-sheet-frame-'.$record->getKey();
                $viewerId = 'sigef-effective-sheet-viewer-'.$record->getKey();

                return view('trainers.sheet-modal', [
                    'viewerId' => $viewerId,
                    'frameId' => $frameId,
                    'documentName' => 'Ficha do Efectivo - '.$effectiveName,
                    'documentBadge' => $identifierLabel.': '.$identifierNumber,
                    'defaultOrientation' => 'vertical',
                    'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                    'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=vertical',
                    'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                    'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=vertical',
                    'loadingText' => 'A preparar ficha do efectivo...',
                    'hintText' => 'Pre-visualize a ficha do efectivo em A4 antes de imprimir.',
                ]);
            });
    }

    protected static function previewCardAction(): Actions\Action
    {
        return Actions\Action::make('preview_card')
            ->label('Imprimir Cartão')
            ->icon('heroicon-o-identification')
            ->color('info')
            ->modalHeading('Pré-visualização do Cartão')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Actions\Action $action) => $action->label('Fechar Pré-visualização')->icon('heroicon-o-x-mark')->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Effective $record) {
                $template = static::cardTemplateForRecord($record);
                $printUrl = route('cartoes.effectives.preview', ['effective' => $record]);
                $cacheBuster = (string) max(
                    (int) ($record->updated_at?->timestamp ?: 0),
                    (int) ($template->updated_at?->timestamp ?: 0),
                    time(),
                );
                $embeddedUrl = fn (string $face): string => $printUrl.'?'.http_build_query([
                    'embedded' => 1,
                    'autoprint' => 0,
                    'face' => $face,
                    'v' => $cacheBuster,
                ]);

                return view('cards.print-modal', [
                    'template' => $template,
                    'payload' => static::cardPayload($record, $template),
                    'viewerId' => 'sigef-effective-card-viewer-'.$record->getKey(),
                    'frameId' => 'sigef-effective-card-frame-'.$record->getKey(),
                    'printUrl' => $printUrl,
                    'embeddedFrontUrl' => $embeddedUrl('front'),
                    'embeddedBackUrl' => $embeddedUrl('back'),
                    'entityLabel' => 'Efectivos',
                    'documentName' => 'Efectivos - '.($record->full_name ?: 'Efectivo'),
                    'statusLabel' => $record->is_active ? 'ACTIVO' : 'INACTIVO',
                    'statusColor' => $record->is_active ? 'success' : 'danger',
                ]);
            });
    }

    public static function cardTemplateForRecord(Effective $record): CardTemplate
    {
        $institutionId = $record->institution_id ?: $record->institution?->id;
        $recordTemplate = $record->cardTemplate;

        if (
            $recordTemplate instanceof CardTemplate
            && (
                filled($recordTemplate->institution_id)
                && (int) $recordTemplate->institution_id === (int) $institutionId
            )
        ) {
            return $recordTemplate;
        }

        if ($recordTemplate instanceof CardTemplate && blank($recordTemplate->institution_id) && filled($institutionId)) {
            $schoolCopy = CardTemplate::query()
                ->where('source_template_id', $recordTemplate->getKey())
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByDesc('updated_at')
                ->first();

            if ($schoolCopy instanceof CardTemplate) {
                return $schoolCopy;
            }

            return $recordTemplate;
        }

        return CardTemplate::resolveForType(
            CardTemplate::TYPE_STAFF,
            $record->staff_type === 'regime_geral' ? 'civil' : 'with_department',
            $institutionId,
        )
            ?: CardTemplate::resolveForType(CardTemplate::TYPE_STAFF, null, $institutionId)
            ?: static::fallbackCardTemplate(CardTemplate::TYPE_STAFF);
    }

    public static function cardPayload(Effective $record, ?CardTemplate $template = null): array
    {
        $template ??= static::fallbackCardTemplate(CardTemplate::TYPE_STAFF);
        $institution = $record->institution;
        $isGeneralRegime = $record->staff_type === 'regime_geral';
        $documentLabel = $isGeneralRegime ? 'BI' : 'NIP';
        $documentNumber = trim((string) (
            $isGeneralRegime
                ? ($record->identity_document ?: $record->document_number ?: $record->identifier)
                : ($record->employee_number ?: $record->document_number ?: $record->identifier)
        ));
        $documentNumber = $documentNumber !== '' ? $documentNumber : str_pad((string) $record->getKey(), 6, '0', STR_PAD_LEFT);
        $codes = app(\App\Services\CardCodeService::class);
        $verificationUrl = url('/admin/effectives').'?tableSearch='.rawurlencode($documentNumber);
        $photo = trim((string) $record->photo);
        $photoUrl = $photo !== ''
            ? (Str::startsWith($photo, ['http://', 'https://', 'data:']) ? $photo : asset('storage/'.ltrim($photo, '/')))
            : null;
        $templateBrandName = trim((string) $template->brand_name);
        $templateSubtitle = trim((string) $template->subtitle);
        $templateAddress = trim((string) $template->address_line);
        $institutionName = trim((string) $institution?->name);
        $institutionAcronym = trim((string) $institution?->acronym);
        $headerName = $templateBrandName !== '' ? $templateBrandName : ($institutionName !== '' ? $institutionName : 'SIGEF');
        $headerSubtitle = $templateSubtitle !== '' ? $templateSubtitle : $institutionAcronym;
        $institutionLocation = $templateAddress !== ''
            ? $templateAddress
            : collect([$institution?->province, $institution?->municipality])->filter()->implode(' / ');

        return [
            'name' => $record->full_name ?: 'Efectivo',
            'number' => $documentNumber,
            'card_number' => $documentNumber,
            'entity_title' => 'EFECTIVO',
            'document_label' => $documentLabel,
            'document_number' => $documentNumber,
            'photo_url' => $photoUrl,
            'logo_url' => $template->logo_url ?: ($institution?->logo ? asset('storage/'.$institution->logo) : asset('images/logo-policia.png')),
            'institution_name' => $headerName,
            'institution_location' => $institutionLocation,
            'brand_name' => $headerName,
            'subtitle' => $headerSubtitle,
            'front_title' => $template->front_title ?: 'CARTÃO DO EFECTIVO',
            'number_label' => $template->number_label ?: $documentLabel,
            'regime' => Effective::staffTypeOptions()[$record->staff_type] ?? 'Regime Especial',
            'rank' => $record->position_label ?: '-',
            'position' => $record->position_label ?: '-',
            'department' => $record->department ?: $record->unit ?: '-',
            'function' => $record->job_function ?: $record->position_label,
            'blood_type' => $record->blood_type ?: '-',
            'phone' => $record->phone ? (str_starts_with($record->phone, '+') ? $record->phone : '+244 '.$record->phone) : '-',
            'email' => $record->email ?: '-',
            'organ' => $record->placement_organ ?: '-',
            'placement_organ' => $record->placement_organ ?: '-',
            'footer_text' => $template->footer_text ?: 'Este cartão identifica o portador na qualidade de efectivo do SIGEF.',
            'signature_label' => $template->signature_label,
            'signatory_name' => $template->signatory_name,
            'signatory_title' => $template->signatory_title,
            'signature_url' => $template->signature_image_url,
            'qr_code_uri' => $codes->qrCodeDataUri($verificationUrl),
            'verification_url' => $verificationUrl,
            'show_qr_code' => (bool) ($template->show_qr_code ?? true),
            'show_barcode' => false,
            'is_active' => (bool) $record->is_active,
            'initials' => collect(explode(' ', trim($record->full_name)))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                ->implode(''),
        ];
    }

    protected static function fallbackCardTemplate(string $type): CardTemplate
    {
        return new CardTemplate([
            'card_type' => $type,
            'name' => 'Cartão de Efectivo',
            'primary_color' => '#061b42',
            'secondary_color' => '#2563eb',
            'text_color' => '#ffffff',
            'front_text_color' => '#061b42',
            'header_text_color' => '#061b42',
            'back_text_color' => '#111827',
            'front_background_color' => '#ffffff',
            'back_background_color' => '#f8fafc',
            'front_title' => 'CARTÃO DO EFECTIVO',
            'number_label' => 'NIP',
            'back_title' => 'Identificação do Efectivo',
            'footer_text' => 'Este cartão identifica o portador na qualidade de efectivo do SIGEF.',
            'show_qr_code' => true,
            'show_barcode' => false,
            'style' => CardTemplate::STYLE_STAFF_EFFECTIVE,
            'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
        ]);
    }

    public static function normalizeIdentityDocument(mixed $identityDocument): ?string
    {
        $identityDocument = preg_replace('/\s+/u', '', trim((string) $identityDocument));

        return $identityDocument === '' ? null : Str::upper($identityDocument);
    }

    protected static function fillEffectiveDataFromIdentityCard(?string $identityDocument, callable $set): void
    {
        $identityDocument = static::normalizeIdentityDocument($identityDocument);

        if ($identityDocument === null) {
            return;
        }

        if (! preg_match('/^\d{9}[A-Z]{2}\d{3}$/', $identityDocument)) {
            Notification::make()
                ->title('Bilhete de Identidade inválido')
                ->body('Informe o BI no formato angolano, por exemplo: 002976322LA032.')
                ->warning()
                ->send();

            return;
        }

        $data = static::lookupIdentityCard($identityDocument);

        if ($data === null || ($data['error'] ?? false) === true) {
            Notification::make()
                ->title('BI não encontrado')
                ->body('Não foi possível obter os dados deste Bilhete de Identidade na API.')
                ->warning()
                ->send();

            return;
        }

        $set('identity_document', $identityDocument);
        $set('document_type', 'Bilhete de Identidade');
        $set('document_number', $identityDocument);

        if (filled($name = static::firstIdentityValue($data, ['name', 'nome', 'full_name', 'nome_completo']))) {
            $set('full_name', static::formatIdentityName($name));
        }

        if (filled($birthDate = static::firstIdentityValue($data, ['data_de_nascimento', 'birth_date', 'data_nascimento', 'nascimento']))) {
            $set('birth_date', static::normalizeIdentityDate($birthDate));
        }

        if (filled($gender = static::extractIdentityGender($data))) {
            $set('gender', $gender);
        }

        if (filled($province = static::extractIdentityProvince($data))) {
            $set('province', $province);

            if (filled($municipality = static::extractIdentityMunicipality($data, $province))) {
                $set('municipality', $municipality);
            }
        }

        if (filled($bloodType = static::firstIdentityValue($data, ['blood_type', 'grupo_sanguineo', 'grupo_sanguíneo', 'tipo_sangue']))) {
            $set('blood_type', Str::upper(trim($bloodType)));
        }

        if (filled($country = static::firstIdentityValue($data, ['country', 'pais', 'país', 'nacionalidade']))) {
            $set('country', static::formatIdentityCountry($country));
        } else {
            $set('country', 'Angola');
        }

        if (filled($fatherName = static::firstIdentityValue($data, ['father_name', 'nome_pai', 'nome_do_pai', 'pai']))) {
            $set('father_name', static::formatIdentityName($fatherName));
        }

        if (filled($motherName = static::firstIdentityValue($data, ['mother_name', 'nome_mae', 'nome_mãe', 'nome_da_mae', 'nome_da_mãe', 'mae', 'mãe']))) {
            $set('mother_name', static::formatIdentityName($motherName));
        }

        if (filled($phone = static::firstIdentityValue($data, ['phone', 'telefone', 'telemovel', 'telemóvel', 'contacto', 'contact']))) {
            $set('phone', $phone);
        }

        if (filled($email = static::firstIdentityValue($data, ['email', 'e_mail', 'mail']))) {
            $set('email', $email);
        }

        Notification::make()
            ->title('Dados do BI carregados')
            ->body('Os dados disponíveis do BI foram preenchidos automaticamente.')
            ->success()
            ->send();
    }

    protected static function lookupIdentityCard(string $identityDocument): ?array
    {
        $cacheKey = 'identity-card-lookup:'.$identityDocument;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $baseUrl = rtrim((string) config('services.identity_card_lookup.url', 'https://consulta.edgarsingui.ao/consultar'), '/');

            $response = Http::acceptJson()
                ->timeout(8)
                ->retry(1, 250)
                ->get($baseUrl.'/'.rawurlencode($identityDocument));

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            if (($data['error'] ?? true) === false) {
                Cache::put($cacheKey, $data, now()->addHours(12));
            }

            return $data;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    protected static function formatIdentityName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?: '';

        return mb_strtoupper($name, 'UTF-8');
    }

    protected static function extractIdentityGender(array $data): ?string
    {
        $gender = static::firstIdentityValue($data, [
            'sexo',
            'genero',
            'gender',
            'sex',
        ]);

        if ($gender === null) {
            return null;
        }

        $gender = static::normalizeIdentityLookupText($gender);

        return match ($gender) {
            'm', 'masculino', 'male', 'homem' => 'Masculino',
            'f', 'feminino', 'female', 'mulher' => 'Feminino',
            default => null,
        };
    }

    protected static function extractIdentityProvince(array $data): ?string
    {
        $province = static::firstIdentityValue($data, [
            'provincia',
            'province',
            'naturalidade',
            'local_nascimento',
            'local_de_nascimento',
            'provincia_nascimento',
            'provincia_de_nascimento',
            'birth_place',
            'birth_province',
            'province_birth',
        ]);

        if ($province === null) {
            return null;
        }

        return static::matchIdentityProvince($province);
    }

    protected static function extractIdentityMunicipality(array $data, ?string $province = null): ?string
    {
        $municipality = static::firstIdentityValue($data, [
            'municipio',
            'município',
            'municipality',
            'naturalidade_municipio',
            'municipio_nascimento',
            'municipio_de_nascimento',
            'birth_municipality',
            'localidade',
            'comuna',
        ]);

        if ($municipality === null) {
            return null;
        }

        return static::matchIdentityMunicipality($municipality, $province);
    }

    protected static function normalizeIdentityDate(mixed $value): ?string
    {
        if (! is_scalar($value) || blank((string) $value)) {
            return null;
        }

        $value = trim((string) $value);

        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return $value;
        }
    }

    protected static function formatIdentityCountry(string $country): string
    {
        $country = trim(preg_replace('/\s+/u', ' ', $country) ?: '');

        return match (static::normalizeIdentityLookupText($country)) {
            'angola', 'angolano', 'angolana' => 'Angola',
            default => $country,
        };
    }

    protected static function firstIdentityValue(array $data, array $keys): ?string
    {
        $keys = array_flip(array_map(static::normalizeIdentityLookupKey(...), $keys));

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedValue = static::firstIdentityValue($value, array_keys($keys));

                if ($nestedValue !== null) {
                    return $nestedValue;
                }

                continue;
            }

            if (! array_key_exists(static::normalizeIdentityLookupKey((string) $key), $keys)) {
                continue;
            }

            if (is_scalar($value) && filled((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }

    protected static function matchIdentityMunicipality(string $municipality, ?string $province = null): ?string
    {
        $normalizedMunicipality = static::normalizeIdentityLookupText($municipality);
        $normalizedMunicipality = preg_replace('/\b(municipio|municipality|mun|de|da|do)\b/u', ' ', $normalizedMunicipality) ?: $normalizedMunicipality;
        $normalizedMunicipality = trim(preg_replace('/\s+/u', ' ', $normalizedMunicipality) ?: '');

        if ($normalizedMunicipality === '') {
            return null;
        }

        $provinceId = filled($province)
            ? Province::query()->where('name', $province)->value('id')
            : null;

        $municipalities = Municipality::query()
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->orderBy('name')
            ->pluck('name')
            ->all();

        foreach ($municipalities as $municipalityName) {
            if (static::normalizeIdentityLookupText((string) $municipalityName) === $normalizedMunicipality) {
                return (string) $municipalityName;
            }
        }

        foreach ($municipalities as $municipalityName) {
            $normalizedName = static::normalizeIdentityLookupText((string) $municipalityName);

            if (strlen($normalizedName) >= 4 && str_contains($normalizedMunicipality, $normalizedName)) {
                return (string) $municipalityName;
            }
        }

        return null;
    }

    protected static function matchIdentityProvince(string $province): ?string
    {
        $normalizedProvince = static::normalizeIdentityLookupText($province);
        $normalizedProvince = preg_replace('/\b(provincia|province|prov|de|da|do)\b/u', ' ', $normalizedProvince) ?: $normalizedProvince;
        $normalizedProvince = trim(preg_replace('/\s+/u', ' ', $normalizedProvince) ?: '');

        $aliases = [
            'kwanza norte' => 'cuanza norte',
            'kwanza sul' => 'cuanza sul',
            'kuando kubango' => 'cuando cubango',
        ];

        $normalizedProvince = $aliases[$normalizedProvince] ?? $normalizedProvince;

        if ($normalizedProvince === '') {
            return null;
        }

        $provinces = Province::query()->orderBy('name')->pluck('name')->all();

        foreach ($provinces as $provinceName) {
            if (static::normalizeIdentityLookupText((string) $provinceName) === $normalizedProvince) {
                return (string) $provinceName;
            }
        }

        foreach ($provinces as $provinceName) {
            $normalizedName = static::normalizeIdentityLookupText((string) $provinceName);

            if (strlen($normalizedName) >= 5 && str_contains($normalizedProvince, $normalizedName)) {
                return (string) $provinceName;
            }
        }

        return null;
    }

    protected static function normalizeIdentityLookupKey(string $key): string
    {
        $key = mb_strtolower(Str::ascii($key), 'UTF-8');

        return trim(preg_replace('/[^a-z0-9]+/u', '_', $key) ?: '', '_');
    }

    protected static function normalizeIdentityLookupText(string $value): string
    {
        $value = mb_strtolower(Str::ascii($value), 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '');
    }

    protected static function municipalityOptions(?string $province): array
    {
        if (! filled($province)) {
            return [];
        }

        $provinceId = Province::query()
            ->where('name', $province)
            ->value('id');

        if (! $provinceId) {
            return [];
        }

        return Municipality::query()
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected static function countryOptions(): array
    {
        return [
            'Angola' => 'Angola',
            'Brasil' => 'Brasil',
            'Cabo Verde' => 'Cabo Verde',
            'Guiné-Bissau' => 'Guiné-Bissau',
            'Moçambique' => 'Moçambique',
            'Portugal' => 'Portugal',
            'São Tomé e Príncipe' => 'São Tomé e Príncipe',
        ];
    }

    protected static function educationLevelOptions(): array
    {
        return [
            'Ensino Primário' => 'Ensino Primário',
            '7ª Classe' => '7ª Classe',
            '8ª Classe' => '8ª Classe',
            '9ª Classe' => '9ª Classe',
            '10ª Classe' => '10ª Classe',
            '11ª Classe' => '11ª Classe',
            '12ª Classe' => '12ª Classe',
            'Ensino Médio Técnico' => 'Ensino Médio Técnico',
            'Bacharelato' => 'Bacharelato',
            'Licenciatura' => 'Licenciatura',
            'Pós-Graduação' => 'Pós-Graduação',
            'Mestrado' => 'Mestrado',
            'Doutoramento' => 'Doutoramento',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEffectives::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Effective') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
