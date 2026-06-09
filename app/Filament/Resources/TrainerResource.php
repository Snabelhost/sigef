<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainerResource\Pages;
use App\Filament\Resources\TrainerResource\RelationManagers;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseMap;
use App\Models\CoursePhase;
use App\Models\Institution;
use App\Models\Province;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Trainer;
use App\Models\TrainerClassAssignment;
use App\Models\TrainerSubjectAuthorization;
use App\Services\TrainerCardService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Closure;
use Throwable;

class TrainerResource extends Resource
{
    protected static ?string $model = Trainer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-presentation-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 0;
    protected static ?string $modelLabel = 'Formador';
    protected static ?string $pluralModelLabel = 'Formadores';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['rank', 'institution'])
            ->withCount([
                'classAssignments as teaching_times_count',
                'classAssignments as assigned_subjects_count' => fn (Builder $query) => $query
                    ->select(DB::raw('count(distinct subject_id)')),
                'classAssignments as assigned_classes_count' => fn (Builder $query) => $query
                    ->select(DB::raw('count(distinct class_id)')),
            ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::trainerFormSchema());
    }

    protected static function trainerFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Tabs::make('trainer_tabs')
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Dados profissionais')
                        ->icon('heroicon-o-briefcase')
                        ->schema(static::professionalDataTabSchemaV2()),
                    \Filament\Schemas\Components\Tabs\Tab::make('Disciplinas e Turmas')
                        ->icon('heroicon-o-academic-cap')
                        ->schema(static::subjectsAndClassesTabSchema()),
                    \Filament\Schemas\Components\Tabs\Tab::make('Carga docente')
                        ->icon('heroicon-o-clock')
                        ->schema(static::teachingLoadTabSchema()),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function professionalDataTabSchemaV2(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Dados profissionais')
                ->schema([
                    \Filament\Schemas\Components\Html::make(static::trainerPhotoUploadStyles()),

                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'lg' => 12,
                    ])
                        ->schema([
                            \Filament\Schemas\Components\Group::make([
                                Forms\Components\FileUpload::make('photo')
                                    ->label('Foto')
                                    ->hiddenLabel()
                                    ->image()
                                    ->disk('public')
                                    ->directory('trainers')
                                    ->acceptedFileTypes(['image/*'])
                                    ->extraInputAttributes([
                                        'accept' => 'image/*',
                                        'data-sigef-photo-input' => 'true',
                                    ])
                                    ->extraAttributes([
                                        'class' => 'sigef-trainer-photo-upload',
                                        'data-sigef-photo-upload' => 'trainer',
                                    ])
                                    ->imageEditor()
                                    ->imagePreviewHeight('10rem')
                                    ->panelAspectRatio('1:1')
                                    ->panelLayout('integrated')
                                    ->placeholder(static::trainerPhotoUploadPlaceholder())
                                    ->maxSize(4096),
                                \Filament\Schemas\Components\Html::make(static::trainerPhotoUploadActions())
                                    ->hiddenOn('view'),
                                \Filament\Schemas\Components\Html::make(fn (?Trainer $record): HtmlString => static::trainerPhotoPreviewTrigger($record))
                                    ->visibleOn('view'),
                            ])
                                ->extraAttributes([
                                    'class' => 'sigef-trainer-photo-view-group',
                                ])
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 3,
                                ]),

                            \Filament\Schemas\Components\Grid::make([
                                'default' => 1,
                                'md' => 2,
                            ])
                                ->schema([
                                    Forms\Components\ToggleButtons::make('trainer_type')
                                        ->label('Tipo de Formador')
                                        ->options([
                                            'Fardado' => 'Regime Especial',
                                            'Civil' => 'Regime Geral',
                                        ])
                                        ->icons([
                                            'Fardado' => 'heroicon-o-shield-check',
                                            'Civil' => 'heroicon-o-user',
                                        ])
                                        ->extraAttributes([
                                            'class' => 'sigef-trainer-type-toggle',
                                        ])
                                        ->default('Fardado')
                                        ->inline()
                                        ->grouped()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set): void {
                                            if ($state === 'Civil') {
                                                $set('rank_id', null);
                                                $set('organ', null);
                                            }
                                        })
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('nip')
                                        ->label('NIP')
                                        ->placeholder('Ex: PROF-001')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(191)
                                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') !== 'Civil'),
                                    Forms\Components\TextInput::make('bilhete')
                                        ->label('Bilhete de Identidade')
                                        ->placeholder('Ex: 002976322LA032')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (?string $state, callable $set): void {
                                            static::fillTrainerDataFromIdentityCard($state, $set);
                                        })
                                        ->mutateStateForValidationUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                                        ->dehydrateStateUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(191)
                                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Civil'),
                                    Forms\Components\TextInput::make('full_name')
                                        ->label('Nome Completo')
                                        ->required()
                                        ->maxLength(191)
                                        ->unique(ignoreRecord: true)
                                        ->validationMessages([
                                            'unique' => 'Já existe um formador com este nome.',
                                        ]),
                                ])
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 9,
                                ]),
                        ])
                        ->columnSpanFull(),

                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                        ->schema([
                            Forms\Components\Select::make('gender')
                                ->label('Sexo')
                                ->options([
                                    'Masculino' => 'Masculino',
                                    'Feminino' => 'Feminino',
                                ])
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('country_origin')
                                ->label('País de Origem')
                                ->options(static::countryOptions())
                                ->default('Angola')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('province')
                                ->label('Província')
                                ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'name')->toArray())
                                ->searchable()
                                ->preload(),
                            Forms\Components\DatePicker::make('birth_date')
                                ->label('Data de nascimento')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            Forms\Components\Select::make('rank_id')
                                ->label('Patente')
                                ->relationship('rank', 'name')
                                ->searchable()
                                ->preload()
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') !== 'Civil'),
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
                                ->placeholder('Ex: Direito Penal, Ciências Policiais')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('job_function')
                                ->label('Função')
                                ->placeholder('Ex: Formador de Direito Penal')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('department')
                                ->label('Departamento')
                                ->maxLength(191),
                            Forms\Components\Select::make('organ')
                                ->label('Órgão de colocação / proveniência')
                                ->options(fn (): array => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn ($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                                ->searchable()
                                ->preload()
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') !== 'Civil'),
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
                            Forms\Components\DatePicker::make('admission_date')
                                ->label('Data de Admissão')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true)
                                ->required(),
                        ])
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('biography')
                        ->label('Biografia')
                        ->placeholder('Resumo profissional, experiência académica e áreas de investigação.')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function trainerPhotoUploadPlaceholder(): string
    {
        return '<span class="sigef-photo-idle">'
            . '<span class="sigef-photo-camera" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 4.5 7.6 7H5.5A2.5 2.5 0 0 0 3 9.5v8A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7h-2.1L15 4.5H9Zm3 13a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Zm0-2a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg></span>'
            . '</span>';
    }

    protected static function trainerPhotoUploadActions(): HtmlString
    {
        return new HtmlString(
            '<div class="sigef-photo-actions" data-sigef-photo-actions="trainer">'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-primary" data-sigef-photo-action="capture"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-9A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7H17l-2.5-3Z"/><circle cx="12" cy="13" r="3"/></svg><span>Capturar</span></button>'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-secondary" data-sigef-photo-action="upload"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg><span>Carregar</span></button>'
            . '</div>'
        );
    }

    protected static function trainerPhotoPreviewTrigger(?Trainer $record): HtmlString
    {
        $photoUrl = static::trainerPhotoUrl($record);

        if ($photoUrl === null) {
            return new HtmlString('');
        }

        $name = trim((string) ($record?->full_name ?: 'Formador'));

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

    protected static function trainerPhotoUrl(?Trainer $record): ?string
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

    protected static function trainerPhotoUploadStyles(): HtmlString
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
        height: 10rem !important;
        min-height: 10rem;
        align-items: center !important;
        justify-items: center !important;
        place-items: center !important;
        color: inherit;
        cursor: pointer;
        overflow: visible;
    }

    .sigef-trainer-photo-upload .filepond--drop-label label {
        display: grid !important;
        width: 100%;
        height: 100%;
        place-items: center !important;
        overflow: visible;
        padding: 0 !important;
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
        position: relative;
        display: grid;
        height: 10rem;
        min-height: 10rem;
        place-items: center;
        width: 100%;
        line-height: 1;
        transform: none;
    }

    .sigef-photo-camera {
        display: grid;
        width: 3.75rem;
        height: 3.75rem;
        place-items: center;
        color: #dce3ed;
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

    .sigef-trainer-photo-view-group {
        position: relative;
        width: 10.25rem;
    }

    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) {
        cursor: zoom-in;
    }

    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) .sigef-trainer-photo-upload,
    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) .filepond--root,
    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) .filepond--drop-label,
    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) .filepond--item,
    .sigef-trainer-photo-view-group:has(.sigef-photo-preview-trigger) .filepond--file-wrapper {
        cursor: zoom-in !important;
    }

    .sigef-photo-preview-trigger {
        position: absolute;
        right: 0.45rem;
        bottom: 0.45rem;
        z-index: 8;
        display: grid;
        width: 2.25rem;
        height: 2.25rem;
        place-items: center;
        border: 0;
        border-radius: 999px;
        background: #061b42;
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(6, 27, 66, 0.22);
        cursor: pointer;
        opacity: 0;
        pointer-events: none;
        transform: translateY(0.25rem) scale(0.92);
        transition: opacity 0.16s ease, transform 0.16s ease, background 0.16s ease;
    }

    .sigef-photo-preview-trigger svg {
        width: 1rem;
        height: 1rem;
    }

    .sigef-trainer-photo-view-group:hover .sigef-photo-preview-trigger,
    .sigef-trainer-photo-view-group:focus-within .sigef-photo-preview-trigger,
    .sigef-photo-preview-trigger:focus-visible {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    .sigef-photo-preview-trigger:hover,
    .sigef-photo-preview-trigger:focus-visible {
        outline: 2px solid rgba(6, 27, 66, 0.22);
        outline-offset: 2px;
        background: #08265f;
    }

    .sigef-photo-preview-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: none;
    }

    .sigef-photo-preview-modal.is-open {
        display: block;
    }

    .sigef-photo-preview-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.68);
    }

    .sigef-photo-preview-dialog {
        position: relative;
        width: min(610px, calc(100vw - 2rem));
        margin: 6vh auto;
        overflow: hidden;
        border-radius: 0.5rem;
        background: #ffffff;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.3);
    }

    .sigef-photo-preview-frame {
        margin: 1.25rem 1.25rem 0;
        border: 1px solid #111827;
        background: #ffffff;
    }

    .sigef-photo-preview-frame img {
        display: block;
        width: 100%;
        max-height: min(70vh, 680px);
        object-fit: contain;
        background: #ffffff;
    }

    .sigef-photo-preview-name {
        margin: 0;
        padding: 0.9rem 1.25rem 1.1rem;
        color: #061b42;
        font-size: 0.95rem;
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
    }

    .sigef-photo-preview-close {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 2;
        display: grid;
        width: 2.25rem;
        height: 2.25rem;
        place-items: center;
        border: 0;
        border-radius: 0.4rem;
        background: #061b42;
        color: #ffffff;
        cursor: pointer;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1;
    }

    .sigef-photo-preview-close:hover,
    .sigef-photo-preview-close:focus-visible {
        background: #08265f;
        outline: 2px solid rgba(255, 255, 255, 0.85);
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

    public static function normalizeIdentityDocument(mixed $identityDocument): ?string
    {
        $identityDocument = preg_replace('/\s+/u', '', trim((string) $identityDocument));

        return $identityDocument === '' ? null : Str::upper($identityDocument);
    }

    protected static function fillTrainerDataFromIdentityCard(?string $identityDocument, callable $set): void
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

        $set('bilhete', $identityDocument);

        if (filled($data['name'] ?? null)) {
            $set('full_name', static::formatIdentityName((string) $data['name']));
        }

        if (filled($data['data_de_nascimento'] ?? null)) {
            $set('birth_date', (string) $data['data_de_nascimento']);
        }

        if (filled($gender = static::extractIdentityGender($data))) {
            $set('gender', $gender);
        }

        if (filled($province = static::extractIdentityProvince($data))) {
            $set('province', $province);
        }

        $set('country_origin', 'Angola');

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

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
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

    protected static function professionalDataTabSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Dados profissionais')
                ->schema([
                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'lg' => 12,
                    ])->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('trainers')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ]),

                        \Filament\Schemas\Components\Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            Forms\Components\ToggleButtons::make('trainer_type')
                                ->label('Tipo de Formador')
                                ->options([
                                    'Fardado' => 'Regime Especial',
                                    'Civil' => 'Regime Geral',
                                ])
                                ->icons([
                                    'Fardado' => 'heroicon-o-shield-check',
                                    'Civil' => 'heroicon-o-user',
                                ])
                                ->default('Fardado')
                                ->inline()
                                ->grouped()
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('nip')
                                ->label('NIP')
                                ->placeholder('Ex: PROF-001')
                                ->unique(ignoreRecord: true)
                                ->maxLength(191),
                            Forms\Components\TextInput::make('full_name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(191)
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'Já existe um formador com este nome.',
                                ]),
                            Forms\Components\Select::make('gender')
                                ->label('Sexo')
                                ->options([
                                    'Masculino' => 'Masculino',
                                    'Feminino' => 'Feminino',
                                ])
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('country_origin')
                                ->label('País de Origem')
                                ->options(static::countryOptions())
                                ->default('Angola')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('province')
                                ->label('Província')
                                ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'name')->toArray())
                                ->searchable()
                                ->preload(),
                            Forms\Components\DatePicker::make('birth_date')
                                ->label('Data de nascimento')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            Forms\Components\Select::make('rank_id')
                                ->label('Patente')
                                ->relationship('rank', 'name')
                                ->searchable()
                                ->preload(),
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
                                ->placeholder('Ex: Direito Penal, Ciências Policiais')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('job_function')
                                ->label('Função')
                                ->placeholder('Ex: Formador de Direito Penal')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('department')
                                ->label('Departamento')
                                ->maxLength(191),
                            Forms\Components\Select::make('organ')
                                ->label('Órgão de colocação / proveniência')
                                ->options(fn (): array => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn ($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('institution_id')
                                ->label('Escola')
                                ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('phone')
                                ->label('Telefone')
                                ->tel()
                                ->prefix('+244')
                                ->placeholder('9XX XXX XXX')
                                ->mask('999 999 999')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->placeholder('Opcional')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('father_name')
                                ->label('Nome do pai')
                                ->maxLength(191),
                            Forms\Components\TextInput::make('mother_name')
                                ->label('Nome da mãe')
                                ->maxLength(191),
                            Forms\Components\DatePicker::make('admission_date')
                                ->label('Data de Admissão')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true)
                                ->required(),
                            Forms\Components\TextInput::make('bilhete')
                                ->label('Bilhete de Identidade')
                                ->maxLength(191)
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Civil'),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 9,
                            ]),

                        Forms\Components\Textarea::make('biography')
                            ->label('Biografia')
                            ->placeholder('Resumo profissional, experiência académica e áreas de investigação.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function subjectsAndClassesTabSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Atribuição de disciplinas')
                ->description('Registe as turmas, disciplinas e tempos lectivos associados ao formador.')
                ->schema([
                    Forms\Components\Repeater::make('classAssignments')
                        ->label('Disciplinas, turmas e tempos')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('academic_year_id')
                                ->label('Ano Lectivo')
                                ->options(fn (): array => AcademicYear::query()
                                    ->orderByDesc('year')
                                    ->pluck('year', 'id')
                                    ->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set): void {
                                    $set('class_id', null);
                                }),
                            Forms\Components\Select::make('course_id_helper')
                                ->label('Curso')
                                ->options(fn (): array => Course::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\Select $component, ?TrainerClassAssignment $record): void {
                                    $component->state($record?->studentClass?->courseMap?->course_id);
                                })
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set): void {
                                    $set('frequency_year', null);
                                    $set('class_id', null);
                                    $set('subject_id', null);
                                }),
                            Forms\Components\Select::make('frequency_year')
                                ->label('Ano Frequência')
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): array => static::phaseOptionsForCourse($get('course_id_helper')))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('shift')
                                ->label('Turno')
                                ->options([
                                    'Manhã' => 'Manhã',
                                    'Tarde' => 'Tarde',
                                    'Noite' => 'Noite',
                                    'Integral' => 'Integral',
                                ])
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('class_id')
                                ->label('Turma')
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): array => static::classOptionsForAssignment(
                                    $get('academic_year_id'),
                                    $get('course_id_helper'),
                                ))
                                ->getOptionLabelUsing(fn ($value): ?string => StudentClass::with(['institution', 'courseMap.course'])->find($value)?->name)
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('subject_id')
                                ->label('Disciplina')
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): array => static::subjectOptionsForCourse($get('course_id_helper')))
                                ->getOptionLabelUsing(fn ($value): ?string => Subject::find($value)?->name)
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('day_of_week')
                                ->label('Dia da Semana')
                                ->options(static::weekDayOptions())
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Hora Início')
                                ->required(),
                            Forms\Components\TimePicker::make('end_time')
                                ->label('Hora Fim')
                                ->required(),
                            Forms\Components\Select::make('lesson_type')
                                ->label('Tipo de Aula')
                                ->options([
                                    'Teórica' => 'Teórica',
                                    'Prática' => 'Prática',
                                    'Teórico-prática' => 'Teórico-prática',
                                ])
                                ->default('Teórica')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('is_active')
                                ->label('Estado')
                                ->options([
                                    1 => 'Activo',
                                    0 => 'Inactivo',
                                ])
                                ->default(1)
                                ->required()
                                ->dehydrateStateUsing(fn ($state): bool => (bool) $state),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::prepareAssignmentData($data))
                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::prepareAssignmentData($data))
                        ->columns(3)
                        ->columnSpanFull()
                        ->addActionLabel('Adicionar disciplina/turma')
                        ->reorderable(false)
                        ->collapsible()
                        ->defaultItems(0),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function teachingLoadTabSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Resumo de tempos, disciplinas e turmas')
                ->schema([
                    Forms\Components\Placeholder::make('resumo_carga_docente')
                        ->label('Resumo da carga docente')
                        ->content(fn (?Trainer $record): HtmlString|string => static::teachingLoadSummary($record)),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function prepareAssignmentData(array $data): array
    {
        $data['assigned_at'] ??= now();
        $data['assigned_by'] ??= auth()->id();

        unset($data['course_id_helper']);

        return $data;
    }

    protected static function phaseOptionsForCourse(mixed $courseId): array
    {
        if (blank($courseId)) {
            return [];
        }

        return CoursePhase::query()
            ->where('course_id', $courseId)
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected static function classOptionsForAssignment(mixed $academicYearId, mixed $courseId): array
    {
        return StudentClass::query()
            ->with(['institution', 'courseMap.course'])
            ->when($academicYearId, fn (Builder $query): Builder => $query->where('academic_year_id', $academicYearId))
            ->when($courseId, fn (Builder $query): Builder => $query->whereHas('courseMap', fn (Builder $courseMapQuery): Builder => $courseMapQuery->where('course_id', $courseId)))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (StudentClass $class): array => [
                $class->id => $class->name
                    . ($class->courseMap?->course?->name ? ' - ' . $class->courseMap->course->name : '')
                    . ($class->institution?->name ? ' (' . $class->institution->name . ')' : ''),
            ])
            ->toArray();
    }

    protected static function subjectOptionsForCourse(mixed $courseId): array
    {
        if (blank($courseId)) {
            return Subject::query()->orderBy('name')->pluck('name', 'id')->toArray();
        }

        $courseMapSubjects = Subject::query()
            ->whereHas('phase', fn (Builder $query): Builder => $query->where('course_id', $courseId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $courseMapSubjects ?: Subject::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected static function teachingLoadSummary(?Trainer $record): HtmlString|string
    {
        if (! $record?->exists) {
            return 'Guarde o formador para consultar a carga docente.';
        }

        $assignments = $record->classAssignments()
            ->with(['subject:id,name', 'studentClass:id,name'])
            ->get()
            ->filter(fn (TrainerClassAssignment $assignment): bool => (bool) $assignment->is_active)
            ->sortBy(fn (TrainerClassAssignment $assignment): array => [
                static::weekDayOrder($assignment->day_of_week),
                static::timeToMinutes($assignment->start_time) ?? 9999,
                $assignment->subject?->name ?? '',
            ])
            ->values();

        $subjects = $assignments->pluck('subject_id')->filter()->unique()->count();
        $classes = $assignments->pluck('class_id')->filter()->unique()->count();
        $weeklyTimes = $assignments->count();
        $totalMinutes = $assignments->sum(fn (TrainerClassAssignment $assignment): int => static::assignmentDurationMinutes($assignment));

        $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
        $classrooms = $classIds->isEmpty()
            ? collect()
            : DB::table('student_class_enrollments')
                ->select('class_id', DB::raw('MIN(classroom) as classroom'))
                ->whereIn('class_id', $classIds)
                ->whereNotNull('classroom')
                ->where('classroom', '<>', '')
                ->groupBy('class_id')
                ->pluck('classroom', 'class_id');

        $cards = [
            ['Tempos semanais', $weeklyTimes],
            ['Disciplinas', $subjects],
            ['Turmas', $classes],
            ['Carga horária', static::formatDuration($totalMinutes)],
        ];

        $cardsHtml = collect($cards)->map(function (array $card): string {
            return '<div style="border:1px solid #cfd6e3;border-radius:6px;background:#fff;padding:12px 14px;min-height:64px;">'
                . '<div style="font-size:11px;font-weight:700;color:#52627a;text-transform:uppercase;line-height:1.1;">' . e($card[0]) . '</div>'
                . '<div style="font-size:21px;font-weight:800;color:#111827;line-height:1.25;margin-top:3px;">' . e((string) $card[1]) . '</div>'
                . '</div>';
        })->implode('');

        $rowsHtml = $assignments->isEmpty()
            ? '<tr><td colspan="5" style="padding:14px;border:1px solid #cfd6e3;text-align:center;color:#64748b;">Sem carga docente registada.</td></tr>'
            : $assignments->map(function (TrainerClassAssignment $assignment) use ($classrooms): string {
                $classroom = $classrooms->get($assignment->class_id) ?: '-';

                return '<tr>'
                    . '<td style="padding:8px 10px;border:1px solid #cfd6e3;">' . e($assignment->subject?->name ?? '-') . '</td>'
                    . '<td style="padding:8px 10px;border:1px solid #cfd6e3;">' . e($assignment->studentClass?->name ?? '-') . '</td>'
                    . '<td style="padding:8px 10px;border:1px solid #cfd6e3;">' . e($assignment->day_of_week ?: '-') . '</td>'
                    . '<td style="padding:8px 10px;border:1px solid #cfd6e3;">' . e(static::formatTimeRange($assignment)) . '</td>'
                    . '<td style="padding:8px 10px;border:1px solid #cfd6e3;">' . e($classroom) . '</td>'
                    . '</tr>';
            })->implode('');

        return new HtmlString(
            '<div style="border:1px solid #e5e7eb;border-radius:8px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.08);overflow:hidden;">'
            . '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;padding:16px;">' . $cardsHtml . '</div>'
            . '<div style="padding:0 16px 16px;overflow-x:auto;">'
            . '<table style="width:100%;border-collapse:collapse;border:1px solid #cfd6e3;font-size:13px;color:#1f2937;">'
            . '<thead><tr style="background:#eef2f7;">'
            . '<th style="padding:9px 10px;border:1px solid #cfd6e3;text-align:left;font-weight:700;">Disciplina</th>'
            . '<th style="padding:9px 10px;border:1px solid #cfd6e3;text-align:left;font-weight:700;">Turma</th>'
            . '<th style="padding:9px 10px;border:1px solid #cfd6e3;text-align:left;font-weight:700;">Dia</th>'
            . '<th style="padding:9px 10px;border:1px solid #cfd6e3;text-align:left;font-weight:700;">Tempo</th>'
            . '<th style="padding:9px 10px;border:1px solid #cfd6e3;text-align:left;font-weight:700;">Sala</th>'
            . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
            . '</div></div>'
        );
    }

    protected static function weekDayOrder(?string $day): int
    {
        $order = array_flip(array_keys(static::weekDayOptions()));

        return $order[$day] ?? 99;
    }

    protected static function assignmentDurationMinutes(TrainerClassAssignment $assignment): int
    {
        $start = static::timeToMinutes($assignment->start_time);
        $end = static::timeToMinutes($assignment->end_time);

        if ($start === null || $end === null) {
            return 0;
        }

        if ($end < $start) {
            $end += 24 * 60;
        }

        return max(0, $end - $start);
    }

    protected static function timeToMinutes(mixed $time): ?int
    {
        if ($time instanceof \DateTimeInterface) {
            return ((int) $time->format('H')) * 60 + (int) $time->format('i');
        }

        if (blank($time)) {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})/', (string) $time, $matches)) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    protected static function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0h';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . 'h';
        }

        if ($hours === 0) {
            return $remainingMinutes . 'm';
        }

        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    protected static function formatTimeRange(TrainerClassAssignment $assignment): string
    {
        $start = static::formatTime($assignment->start_time);
        $end = static::formatTime($assignment->end_time);

        if ($start === '-' && $end === '-') {
            return '-';
        }

        return $start . ' - ' . $end;
    }

    protected static function formatTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        if (blank($time)) {
            return '-';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', (string) $time, $matches)) {
            return str_pad((string) $matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
        }

        return (string) $time;
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

    protected static function weekDayOptions(): array
    {
        return [
            'Segunda-feira' => 'Segunda-feira',
            'Terça-feira' => 'Terça-feira',
            'Quarta-feira' => 'Quarta-feira',
            'Quinta-feira' => 'Quinta-feira',
            'Sexta-feira' => 'Sexta-feira',
            'Sábado' => 'Sábado',
            'Domingo' => 'Domingo',
        ];
    }

    protected static function legacyWizardForm(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Wizard::make([
                    \Filament\Schemas\Components\Wizard\Step::make('Tipo')
                        ->description('Selecione o tipo de formador')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\Select::make('trainer_type')
                                ->label('Tipo de Formador')
                                ->options([
                                    'Fardado' => 'REGIME ESPECIAL',
                                    'Civil' => 'REGIME GERAL',
                                ])
                                ->default('Fardado')
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                        ]),

                    \Filament\Schemas\Components\Wizard\Step::make('Identificação')
                        ->description('Dados pessoais do formador')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\FileUpload::make('photo')
                                ->label('Foto')
                                ->image()
                                ->avatar()
                                ->disk('public')
                                ->directory('trainers')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('full_name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(191)
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'Já existe um formador com este nome.',
                                ]),
                            Forms\Components\Select::make('gender')
                                ->label('Género')
                                ->options([
                                    'Masculino' => 'Masculino',
                                    'Feminino' => 'Feminino',
                                ])
                                ->required(),

                            // Campos para Fardado
                            \Filament\Schemas\Components\Fieldset::make('Dados Pessoais')
                                ->schema([
                                    Forms\Components\TextInput::make('nip')
                                        ->label('NIP')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(191),
                                    Forms\Components\Select::make('rank_id')
                                        ->label('Patente')
                                        ->relationship('rank', 'name')
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('organ')
                                        ->label('Órgão/Unidade')
                                        ->options(fn() => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                                        ->searchable()
                                        ->preload(),
                                ])->columns(3)
                                ->columnSpanFull()
                                ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Fardado'),

                            // Campos para Civil
                            \Filament\Schemas\Components\Fieldset::make('Dados Pessoais')
                                ->schema([
                                    Forms\Components\TextInput::make('bilhete')
                                        ->label('Bilhete de Identidade')
                                        ->maxLength(191)
                                        ->columnSpanFull(),
                                ])->columns(1)
                                ->columnSpanFull()
                                ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Civil'),
                        ])->columns(2),

                    \Filament\Schemas\Components\Wizard\Step::make('Disciplinas')
                        ->description('Atribuir disciplinas ao formador')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Disciplinas Autorizadas')
                                ->description('Selecione as disciplinas que o formador pode leccionar em cada instituição')
                                ->schema([
                                    Forms\Components\Repeater::make('subjectAuthorizations')
                                        ->label('Autorizações de Disciplinas')
                                        ->relationship()
                                        ->schema([
                                            Forms\Components\Select::make('institution_id')
                                                ->label('Instituição')
                                                ->options(fn() => \App\Models\Institution::orderBy('name')->pluck('name', 'id'))
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Institution::find($value)?->name)
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live(debounce: 0)
                                                ->afterStateUpdated(function ($set) {
                                                    $set('course_id', null);
                                                    $set('subject_id', null);
                                                }),
                                            Forms\Components\Select::make('course_id')
                                                ->label('Curso')
                                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                                    $institutionId = $get('institution_id');
                                                    if (!$institutionId) {
                                                        return [];
                                                    }
                                                    return \App\Models\CourseMap::where('course_maps.institution_id', $institutionId)
                                                        ->join('courses', 'course_maps.course_id', '=', 'courses.id')
                                                        ->orderBy('courses.name')
                                                        ->pluck('courses.name', 'course_maps.course_id')
                                                        ->toArray();
                                                })
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Course::find($value)?->name)
                                                ->searchable()
                                                ->required()
                                                ->live(debounce: 0)
                                                ->afterStateUpdated(fn($set) => $set('subject_id', null)),
                                            Forms\Components\Select::make('subject_id')
                                                ->label('Disciplina')
                                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                                    $courseId = $get('course_id');
                                                    if (!$courseId) {
                                                        return [];
                                                    }
                                                    $coursePlan = \App\Models\CoursePlan::where('course_id', $courseId)
                                                        ->where('is_active', true)
                                                        ->first();

                                                    if (!$coursePlan) {
                                                        return \App\Models\Subject::orderBy('name')->pluck('name', 'id')->toArray();
                                                    }

                                                    return $coursePlan->subjects()->orderBy('name')->pluck('name', 'subjects.id')->toArray();
                                                })
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Subject::find($value)?->name)
                                                ->searchable()
                                                ->required(),
                                        ])
                                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): ?array {
                                            $trainerId = $record?->id;
                                            $institutionId = $data['institution_id'] ?? null;
                                            $courseId = $data['course_id'] ?? null;
                                            $subjectId = $data['subject_id'] ?? null;

                                            // Verificar se já existe esta combinação para ESTE formador
                                            $existsForThis = \App\Models\TrainerSubjectAuthorization::where([
                                                'trainer_id' => $trainerId,
                                                'institution_id' => $institutionId,
                                                'course_id' => $courseId,
                                                'subject_id' => $subjectId,
                                            ])->exists();

                                            if ($existsForThis && $trainerId) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Disciplina já atribuída!')
                                                    ->body('Esta combinação de instituição, curso e disciplina já existe para este formador.')
                                                    ->danger()
                                                    ->duration(5000)
                                                    ->send();
                                                return null; // Não criar
                                            }

                                            // Verificar se OUTRO formador já tem esta disciplina
                                            $existingTrainer = \App\Models\TrainerSubjectAuthorization::getExistingTrainer(
                                                $institutionId,
                                                $courseId,
                                                $subjectId,
                                                $trainerId
                                            );

                                            if ($existingTrainer) {
                                                $trainerName = $existingTrainer->candidate?->full_name ?? 'Outro formador';
                                                \Filament\Notifications\Notification::make()
                                                    ->title('⚠️ Atenção: Disciplina já atribuída a outro formador!')
                                                    ->body("A disciplina já está atribuída a \"{$trainerName}\". Se continuar, haverá dois formadores para a mesma disciplina.")
                                                    ->warning()
                                                    ->duration(8000)
                                                    ->send();
                                                // Permite criar mas mostra aviso
                                            }

                                            $data['authorized_by'] = auth()->id();
                                            return $data;
                                        })
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->addActionLabel('Adicionar Disciplina')
                                        ->reorderable(false)
                                        ->defaultItems(0),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Wizard\Step::make('Finalização')
                        ->description('Informações adicionais')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Select::make('education_level')
                                ->label('Nível Académico')
                                ->options([
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
                                ])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('phone')
                                ->label('Telefone')
                                ->tel()
                                ->prefix('+244')
                                ->placeholder('9XX XXX XXX')
                                ->mask('999 999 999')
                                ->maxLength(191)
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true)
                                ->required(),
                        ])->columns(5),
                ])
                    ->skippable()
                    ->persistStepInQueryString()
                    ->columnSpanFull(),
            ]);
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
                    ->size(40)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name ?? 'F') . '&background=0D47A1&color=fff&size=128'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_number')
                    ->label('Nº Efectivo')
                    ->getStateUsing(fn(Trainer $record): ?string => $record->nip ?: $record->bilhete)
                    ->searchable(query: fn(Builder $query, string $search): Builder => $query
                        ->where(fn(Builder $query): Builder => $query
                            ->where('nip', 'like', "%{$search}%")
                            ->orWhere('bilhete', 'like', "%{$search}%")))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('rank.name')
                    ->label('Patente')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('situation')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $state ?: 'Efectivo')
                    ->color(fn(?string $state): string => match ($state) {
                        'Inactivo' => 'danger',
                        'Reformado' => 'warning',
                        default => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->formatStateUsing(function (?string $state): string {
                        $phone = trim((string) $state);

                        if ($phone === '') {
                            return '-';
                        }

                        return str_starts_with($phone, '+') ? $phone : '+244 ' . $phone;
                    })
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('assigned_subjects_count')
                    ->label('Disciplinas')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assigned_classes_count')
                    ->label('Turmas')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('teaching_times_count')
                    ->label('Tempos')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('trainer_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'Fardado' => 'REGIME ESPECIAL',
                        'Civil' => 'REGIME GERAL',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'Fardado',
                        'success' => 'Civil',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Escola')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bilhete')
                    ->label('Bilhete')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trainer_type')
                    ->label('Tipo')
                    ->options([
                        'Fardado' => 'REGIME ESPECIAL',
                        'Civil' => 'REGIME GERAL',
                    ]),
                Tables\Filters\SelectFilter::make('rank_id')
                    ->label('Patente')
                    ->relationship('rank', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('situation')
                    ->label('Situação')
                    ->options([
                        'Efectivo' => 'Efectivo',
                        'Contratado' => 'Contratado',
                        'Convidado' => 'Convidado',
                        'Reformado' => 'Reformado',
                        'Inactivo' => 'Inactivo',
                    ])
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Turma')
                    ->options(fn (): array => StudentClass::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('classAssignments', fn (Builder $query): Builder => $query
                            ->where('class_id', $data['value'])
                            ->where('is_active', true))
                        : $query)
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn (): array => Subject::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('classAssignments', fn (Builder $query): Builder => $query
                            ->where('subject_id', $data['value'])
                            ->where('is_active', true))
                        : $query)
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->headerActions([
                // Botão Importar Excel
                \Filament\Actions\Action::make('importarExcel')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                    ])
                    ->modalHeading('Importar Formadores do Excel')
                    ->modalDescription(new \Illuminate\Support\HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos formadores.</span>'))
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

                        if (!file_exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro')
                                ->body('Arquivo não encontrado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $import = new \App\Imports\TrainerImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);

                            $stats = $import->getImportStats();
                            $detailedErrors = $import->getDetailedErrors();

                            @unlink($filePath);

                            if ($stats['imported'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Importação Concluída')
                                    ->body("Importados: {$stats['imported']} formadores!")
                                    ->success()
                                    ->send();
                            }

                            if ($stats['skipped'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Registros Ignorados')
                                    ->body("{$stats['skipped']} já existiam.")
                                    ->warning()
                                    ->send();
                            }

                            if (count($detailedErrors) > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Problemas Encontrados')
                                    ->body(implode("\n", array_slice($detailedErrors, 0, 5)))
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro na Importação')
                                ->body('Erro: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Importar')->icon('heroicon-o-arrow-up-tray'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                // Botão Baixar Modelo
                \Filament\Actions\Action::make('baixarModelo')
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\TrainerTemplateExport(), 'modelo_importacao_formadores.xlsx');
                    }),
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth(Width::ScreenExtraLarge)
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!')
                    ->label('Novo Formador'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Formador')
                        ->modalWidth(Width::ScreenExtraLarge)
                        ->schema(static::trainerFormSchema())
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\Action::make('trainer_sheet')
                        ->label('Ficha do Professor')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->modalHeading('Pre-visualizacao da Ficha do Professor')
                        ->modalDescription(null)
                        ->modalWidth(Width::SixExtraLarge)
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                            ->icon('heroicon-o-x-mark')
                            ->label('Fechar Pre-visualizacao')
                            ->color('danger'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->closeModalByClickingAway(false)
                        ->modalContent(function (Trainer $record) {
                            $printUrl = route('trainers.sheet.print', ['trainer' => $record]);
                            $trainerName = trim((string) ($record->full_name ?: 'Professor'));
                            $identifierLabel = filled($record->bilhete) ? 'N&ordm; DO BI' : 'NIP';
                            $identifierNumber = trim((string) ($record->bilhete ?: $record->nip ?: '-'));
                            $frameId = 'sigef-trainer-sheet-frame-'.$record->getKey();
                            $viewerId = 'sigef-trainer-sheet-viewer-'.$record->getKey();

                            return view('trainers.sheet-modal', [
                                'viewerId' => $viewerId,
                                'frameId' => $frameId,
                                'documentName' => 'Ficha do Professor - '.$trainerName,
                                'documentBadge' => $identifierLabel.': '.$identifierNumber,
                                'defaultOrientation' => 'horizontal',
                                'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                                'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=vertical',
                                'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                                'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=vertical',
                            ]);
                        }),
                    static::printCardAction(),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth(Width::ScreenExtraLarge)
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function printCardAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('print_card')
            ->label('Imprimir Cartão')
            ->icon('heroicon-o-identification')
            ->color('warning')
            ->modalHeading('Pré-visualização do Cartão')
            ->modalDescription(null)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pré-visualização')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Trainer $record) {
                $data = app(TrainerCardService::class)->build($record);

                return view('cards.preview-modal', $data + [
                    'entityLabel' => 'Formadores',
                    'documentName' => 'Formadores - '.($record->full_name ?: 'Formador'),
                    'statusLabel' => $record->is_active ? 'ACTIVO' : 'INACTIVO',
                    'statusColor' => $record->is_active ? 'success' : 'danger',
                ]);
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainers::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Trainer') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
