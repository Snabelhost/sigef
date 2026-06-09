<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Livewire\WithFileUploads;

class InstitutionReportSettings extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Configurar Instituição';
    protected static ?string $title = 'Configurar Instituição';
    protected static ?string $slug = 'configuracoes/instituicao';

    protected string $view = 'filament.pages.institution-report-settings';

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
    public $report_institution_logo_path = '';
    public ?string $report_institution_footer_text = '';

    public function mount(): void
    {
        $config = SystemSetting::getReportInstitutionConfig();

        foreach ($config as $key => $value) {
            $property = "report_institution_{$key}";

            if (property_exists($this, $property)) {
                $this->{$property} = (string) $value;
            }
        }
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                            ->columnSpanFull(),
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
                        Forms\Components\Textarea::make('report_institution_footer_text')
                            ->label('Texto do rodapé')
                            ->rows(3)
                            ->placeholder('Ex: Documento emitido pelo SIGEF.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        foreach (array_keys(SystemSetting::getReportInstitutionConfig()) as $key) {
            $property = "report_institution_{$key}";
            $value = $this->{$property} ?? '';

            if (is_array($value)) {
                $value = reset($value) ?: '';
            }

            SystemSetting::set($property, (string) $value, 'report_institution');
        }

        Notification::make()
            ->title('Configuração da instituição guardada!')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();
    }
}
