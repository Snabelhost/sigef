<?php

namespace App\Filament\Pages;

use App\Models\Institution;
use App\Models\SystemSetting;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class InstitutionReportSettings extends Page
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Configurar Instituição';
    protected static ?string $title = 'Configurar Instituição';
    protected static ?string $slug = 'configuracoes/instituicao';

    protected string $view = 'filament.pages.institution-report-settings';

    public int|string|null $institution_id = null;
    public ?string $report_institution_republic_line = '';
    public ?string $report_institution_ministry_line = '';
    public ?string $report_institution_organ_line = '';
    public ?string $report_institution_department_line = '';
    public ?string $report_institution_name = '';
    public ?string $report_institution_acronym = '';
    public ?string $report_institution_director_name = '';
    public ?string $report_institution_director_title = '';
    public ?string $report_institution_nif = '';
    public ?string $report_institution_phone = '';
    public ?string $report_institution_email = '';
    public ?string $report_institution_website = '';
    public ?string $report_institution_country = '';
    public ?string $report_institution_province = '';
    public ?string $report_institution_municipality = '';
    public ?string $report_institution_address = '';
    public array $report_institution_logo_path = [];
    public array $report_institution_watermark_path = [];
    public ?string $report_institution_certificate_school_name = '';
    public ?string $report_institution_certificate_left_signature_title = '';
    public ?string $report_institution_certificate_left_signature_subtitle = '';
    public ?string $report_institution_certificate_right_signature_title = '';
    public ?string $report_institution_certificate_right_signature_subtitle = '';
    public ?string $report_institution_footer_text = '';

    public function mount(): void
    {
        $this->institution_id = Filament::getTenant()?->id
            ?: auth()->user()?->institution_id
            ?: Institution::query()->orderBy('name')->value('id');

        $this->loadInstitutionConfig();
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Instituição dos relatórios')
                    ->description('Escolha a instituição antes de configurar o cabeçalho, contactos e rodapé dos relatórios.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição')
                            ->options(fn() => Institution::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $this->institution_id = filled($state) ? (int) $state : null;
                                $this->loadInstitutionConfig($set);
                            })
                            ->helperText('Cada instituição pode ter logótipo, cabeçalho, responsável, contactos e rodapé próprios.'),
                    ]),

                \Filament\Schemas\Components\Section::make('Cabeçalho dos relatórios')
                    ->description('Linhas institucionais exibidas no topo das fichas e relatórios.')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('report_institution_logo_path')
                            ->label('Logótipo')
                            ->image()
                            ->disk('public')
                            ->directory('report-institution')
                            ->imageEditor()
                            ->maxFiles(1)
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('report_institution_watermark_path')
                            ->label('Marca de água')
                            ->image()
                            ->disk('public')
                            ->directory('report-institution/watermarks')
                            ->imageEditor()
                            ->maxFiles(1)
                            ->helperText('Se não for informada, será usada a mesma imagem do logótipo.')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('report_institution_republic_line')
                            ->label('Linha 1')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_ministry_line')
                            ->label('Linha 2')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_organ_line')
                            ->label('Linha 3')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_department_line')
                            ->label('Linha 4')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_name')
                            ->label('Nome da instituição')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_acronym')
                            ->label('Sigla')
                            ->maxLength(80),
                    ]),

                \Filament\Schemas\Components\Section::make('Dados da instituição')
                    ->description('Contactos, localização e responsável da instituição.')
                    ->icon('heroicon-o-building-library')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('report_institution_director_name')
                            ->label('Responsável')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_director_title')
                            ->label('Cargo do responsável')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_nif')
                            ->label('NIF')
                            ->maxLength(80),
                        Forms\Components\TextInput::make('report_institution_phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(80),
                        Forms\Components\TextInput::make('report_institution_email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_website')
                            ->label('Website')
                            ->url()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_country')
                            ->label('País')
                            ->maxLength(120),
                        Forms\Components\TextInput::make('report_institution_province')
                            ->label('Província')
                            ->maxLength(120),
                        Forms\Components\TextInput::make('report_institution_municipality')
                            ->label('Município')
                            ->maxLength(120),
                        Forms\Components\Textarea::make('report_institution_address')
                            ->label('Endereço')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Rodapé dos relatórios')
                    ->description('Texto exibido no rodapé das fichas e relatórios.')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        Forms\Components\TextInput::make('report_institution_certificate_school_name')
                            ->label('Escola no cabeçalho do certificado')
                            ->helperText('Use este campo quando o texto do certificado deve mostrar uma escola diferente do nome administrativo da instituição.')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_certificate_left_signature_title')
                            ->label('Certificado - assinatura esquerda / cargo')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_certificate_left_signature_subtitle')
                            ->label('Certificado - assinatura esquerda / patente')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_certificate_right_signature_title')
                            ->label('Certificado - assinatura direita / cargo')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('report_institution_certificate_right_signature_subtitle')
                            ->label('Certificado - assinatura direita / patente')
                            ->maxLength(191),
                        Forms\Components\Textarea::make('report_institution_footer_text')
                            ->label('Texto do rodapé')
                            ->rows(3)
                            ->placeholder('Ex: Documento emitido pelo SIGEF.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->schema->getState();
        $this->institution_id = filled($state['institution_id'] ?? $this->institution_id)
            ? (int) ($state['institution_id'] ?? $this->institution_id)
            : null;

        $institution = $this->institution_id
            ? Institution::query()->find((int) $this->institution_id)
            : null;

        if (! $institution) {
            Notification::make()
                ->title('Selecione uma instituição.')
                ->body('É necessário escolher a instituição antes de guardar as configurações dos relatórios.')
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->send();

            return;
        }

        if (blank($state['report_institution_name'] ?? null)) {
            Notification::make()
                ->title('Informe o nome da instituição.')
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->send();

            return;
        }

        foreach (SystemSetting::getReportInstitutionKeys() as $key) {
            $property = "report_institution_{$key}";
            $value = $this->normalizeValueForStorage($state[$property] ?? $this->{$property} ?? '');

            SystemSetting::setReportInstitutionConfigValue($key, $value, (int) $this->institution_id);
        }

        $this->loadInstitutionConfig();

        Notification::make()
            ->title('Configuração da instituição guardada!')
            ->body('Os relatórios desta instituição passam a usar estes dados automaticamente.')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();

    }

    private function loadInstitutionConfig(?Set $set = null): void
    {
        $state = $this->getInstitutionFormState();

        foreach ($state as $property => $value) {
            if (! property_exists($this, $property)) {
                continue;
            }

            $this->{$property} = $value;

            if ($set) {
                $set($property, $value);
            }
        }

    }

    private function getInstitutionFormState(): array
    {
        $config = SystemSetting::getReportInstitutionConfig($this->institution_id);
        $state = [
            'institution_id' => filled($this->institution_id) ? (int) $this->institution_id : null,
        ];

        foreach ($config as $key => $value) {
            $property = "report_institution_{$key}";
            $state[$property] = $this->normalizeValueForForm($key, $value);
        }

        return $state;
    }

    private function normalizeValueForForm(string $key, mixed $value): mixed
    {
        if (in_array($key, ['logo_path', 'watermark_path'], true)) {
            $path = $this->normalizeStoredFilePath($value);

            return $path ? [$path] : [];
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function normalizeValueForStorage(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value) ?: '';
        }

        if ($value instanceof TemporaryUploadedFile) {
            return $value->store('report-institution', 'public') ?: '';
        }

        return $this->normalizeStoredFilePath($value) ?: '';
    }

    private function normalizeStoredFilePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        if ($value instanceof TemporaryUploadedFile) {
            return $value->store('report-institution', 'public') ?: null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeStoredFilePath(reset($decoded) ?: null);
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, strlen('/storage/')), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, strlen('storage/')), '/');
        }

        return $value;
    }
}
