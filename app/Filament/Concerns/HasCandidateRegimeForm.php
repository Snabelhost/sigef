<?php

namespace App\Filament\Concerns;

use App\Models\Effective;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Provenance;
use App\Models\Rank;
use App\Services\IdentityCardLookupService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;
use Throwable;

trait HasCandidateRegimeForm
{
    protected static function candidateIdentificationSection(): \Filament\Schemas\Components\Section
    {
        return \Filament\Schemas\Components\Section::make('Identificação Pessoal')
            ->icon('heroicon-o-user')
            ->description('Dados pessoais e regime do formando')
            ->schema([
                Forms\Components\ToggleButtons::make('staff_type')
                    ->label('Regime')
                    ->options([
                        'regime_geral' => 'Regime Geral',
                        'regime_especial' => 'Regime Especial',
                    ])
                    ->icons([
                        'regime_geral' => 'heroicon-o-identification',
                        'regime_especial' => 'heroicon-o-shield-check',
                    ])
                    ->extraAttributes([
                        'class' => 'sigef-candidate-regime-toggle',
                    ])
                    ->default('regime_geral')
                    ->inline()
                    ->grouped()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state === 'regime_geral') {
                            $set('nuri', null);
                            $set('current_rank_id', null);
                            $set('provenance_id', null);
                        }

                        if ($state === 'regime_especial') {
                            $set('id_number', null);
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('id_number')
                    ->label('Nº do BI')
                    ->placeholder('Ex: 007397943LA048')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        static::fillCandidateDataFromIdentityCard($state, $set);
                    })
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                    ->dehydrateStateUsing(fn (?string $state): ?string => static::normalizeIdentityDocument($state))
                    ->unique(ignoreRecord: true)
                    ->maxLength(191)
                    ->required(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_geral')
                    ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_geral')
                    ->validationMessages([
                        'unique' => 'Já existe um formando com este Nº de BI.',
                    ]),

                Forms\Components\TextInput::make('nuri')
                    ->label('NIP')
                    ->placeholder('Ex: 1057728')
                    ->unique(ignoreRecord: true)
                    ->maxLength(191)
                    ->required(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_especial')
                    ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_especial')
                    ->validationMessages([
                        'unique' => 'Já existe um formando com este NIP.',
                    ]),

                Forms\Components\TextInput::make('full_name')
                    ->label('Nome Completo')
                    ->required()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Já existe um formando com este nome.',
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

                Forms\Components\Select::make('blood_type')
                    ->label('Grupo Sanguíneo')
                    ->options(Effective::bloodTypeOptions())
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('country')
                    ->label('País de Origem')
                    ->options(static::candidateCountryOptions())
                    ->default('Angola')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('province_id')
                    ->label('Província')
                    ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('municipality_id', null);
                    }),

                Forms\Components\Select::make('municipality_id')
                    ->label('Município')
                    ->options(fn (Get $get): array => static::candidateMunicipalityOptions($get('province_id')))
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\DatePicker::make('birth_date')
                    ->label('Data de Nascimento')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),

                Forms\Components\Select::make('current_rank_id')
                    ->label('Patente')
                    ->options(fn (): array => Rank::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_especial'),

                Forms\Components\Select::make('provenance_id')
                    ->label('Órgão de colocação / proveniência')
                    ->options(fn (): array => Provenance::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Provenance $provenance): array => [
                            $provenance->id => $provenance->acronym
                                ? "{$provenance->name} ({$provenance->acronym})"
                                : $provenance->name,
                        ])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => ($get('staff_type') ?? 'regime_geral') === 'regime_especial'),

                Forms\Components\Select::make('marital_status')
                    ->label('Estado Civil')
                    ->options([
                        'solteiro' => 'Solteiro(a)',
                        'casado' => 'Casado(a)',
                        'divorciado' => 'Divorciado(a)',
                        'viuvo' => 'Viúvo(a)',
                    ])
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('father_name')
                    ->label('Nome do Pai')
                    ->maxLength(191),

                Forms\Components\TextInput::make('mother_name')
                    ->label('Nome da Mãe')
                    ->maxLength(191),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->prefix('+244')
                    ->placeholder('9XX XXX XXX')
                    ->mask('999 999 999')
                    ->maxLength(191)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Já existe um formando com este telefone.',
                    ]),

                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Já existe um formando com este e-mail.',
                    ]),

                Forms\Components\TextInput::make('education_level')
                    ->label('Habilitações literárias')
                    ->maxLength(191),

                Forms\Components\TextInput::make('education_area')
                    ->label('Área de formação')
                    ->maxLength(191),

                Forms\Components\Textarea::make('address')
                    ->label('Endereço')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    protected static function candidateClassificationSection(array $statusOptions, string $defaultStatus): \Filament\Schemas\Components\Section
    {
        return \Filament\Schemas\Components\Section::make('Classificação')
            ->icon('heroicon-o-check-badge')
            ->description('Tipo de registo e resultado do recrutamento')
            ->schema([
                Forms\Components\Select::make('student_type')
                    ->label('Tipo')
                    ->options([
                        'Alistado' => 'Alistado',
                        'Formando' => 'Formando',
                    ])
                    ->default('Alistado')
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->label('Resultado')
                    ->options($statusOptions)
                    ->default($defaultStatus)
                    ->required()
                    ->native(false),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    protected static function normalizeCandidateRegimeData(array $data): array
    {
        $data['staff_type'] = $data['staff_type'] ?? 'regime_geral';
        $data['country'] = filled($data['country'] ?? null) ? $data['country'] : 'Angola';

        if ($data['staff_type'] === 'regime_geral') {
            $data['id_number'] = static::normalizeIdentityDocument($data['id_number'] ?? null);
            $data['nuri'] = null;
            $data['current_rank_id'] = null;
            $data['provenance_id'] = null;
        }

        if ($data['staff_type'] === 'regime_especial') {
            $data['id_number'] = null;
        }

        return $data;
    }

    public static function normalizeIdentityDocument(mixed $identityDocument): ?string
    {
        return app(IdentityCardLookupService::class)->normalizeDocument($identityDocument);
    }

    protected static function fillCandidateDataFromIdentityCard(?string $identityDocument, callable $set): void
    {
        $service = app(IdentityCardLookupService::class);
        $identityDocument = $service->normalizeDocument($identityDocument);

        if ($identityDocument === null) {
            return;
        }

        if (! $service->isValidAngolanDocument($identityDocument)) {
            Notification::make()
                ->title('Bilhete de Identidade inválido')
                ->body('Informe o BI no formato angolano, por exemplo: 002976322LA032.')
                ->warning()
                ->send();

            return;
        }

        $data = $service->lookup($identityDocument);

        if ($data === null || ($data['error'] ?? false) === true) {
            Notification::make()
                ->title('BI não encontrado')
                ->body('Não foi possível obter os dados deste Bilhete de Identidade na API.')
                ->warning()
                ->send();

            return;
        }

        $set('id_number', $identityDocument);
        $set('country', $service->firstValue($data, ['country', 'pais', 'país', 'nacionalidade']) ?: 'Angola');

        if (filled($name = $service->firstValue($data, ['name', 'nome', 'full_name', 'nome_completo']))) {
            $set('full_name', $service->formatName($name));
        }

        if (filled($birthDate = $service->firstValue($data, ['data_de_nascimento', 'birth_date', 'data_nascimento', 'nascimento']))) {
            $set('birth_date', static::normalizeIdentityDate($birthDate));
        }

        if (filled($gender = $service->extractGender($data))) {
            $set('gender', $gender);
        }

        if (filled($fatherName = $service->firstValue($data, ['father_name', 'nome_pai', 'nome_do_pai', 'pai']))) {
            $set('father_name', $service->formatName($fatherName));
        }

        if (filled($motherName = $service->firstValue($data, ['mother_name', 'nome_mae', 'nome_mãe', 'nome_da_mae', 'nome_da_mãe', 'mae', 'mãe']))) {
            $set('mother_name', $service->formatName($motherName));
        }

        if (filled($address = $service->firstValue($data, ['address', 'endereco', 'endereço', 'morada', 'residencia', 'residência']))) {
            $set('address', $address);
        }

        $provinceName = $service->extractProvinceName($data);
        $provinceId = $service->provinceId($provinceName);

        if ($provinceId !== null) {
            $set('province_id', $provinceId);

            $municipalityName = $service->extractMunicipalityName($data, $provinceId);
            $municipalityId = $service->municipalityId($municipalityName, $provinceId);

            if ($municipalityId !== null) {
                $set('municipality_id', $municipalityId);
            }
        }

        Notification::make()
            ->title('Dados do BI carregados')
            ->body('Os dados disponíveis do BI foram preenchidos automaticamente.')
            ->success()
            ->send();
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

    protected static function candidateMunicipalityOptions(mixed $provinceId): array
    {
        if (! filled($provinceId)) {
            return [];
        }

        return Municipality::query()
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function candidateCountryOptions(): array
    {
        $countries = [
            'Angola', 'África do Sul', 'Brasil', 'Cabo Verde', 'Congo', 'Cuba',
            'Moçambique', 'Namíbia', 'Portugal', 'São Tomé e Príncipe', 'Zâmbia',
        ];

        return array_combine($countries, $countries);
    }
}
