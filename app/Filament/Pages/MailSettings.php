<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class MailSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-at-symbol';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationLabel = 'Servidor de Email';
    protected static ?string $title = 'Configurações de Comunicação';
    protected static ?string $slug = 'mail-settings';

    protected string $view = 'filament.pages.mail-settings';

    // SMTP Form state
    public ?string $mail_mailer = 'smtp';
    public ?string $mail_host = '';
    public ?string $mail_port = '587';
    public ?string $mail_encryption = 'tls';
    public ?string $mail_username = '';
    public ?string $mail_password = '';
    public ?string $mail_from_address = '';
    public ?string $mail_from_name = 'SIGEF';

    // SMS Form state
    public ?string $sms_provider = 'telcosms';
    public ?string $sms_api_url = 'https://telcosms.co.ao/send_message';
    public ?string $sms_api_key = 'prd09933ffaa3022ca9d71dc39719';
    public ?string $sms_api_secret = '';
    public ?string $sms_sender_id = 'SIGEF';
    public bool $sms_enabled = false;

    // Active tab
    public string $activeTab = 'smtp';

    public function mount(): void
    {
        // SMTP settings
        $this->mail_mailer       = SystemSetting::get('mail_mailer', config('mail.default', 'smtp'));
        $this->mail_host         = SystemSetting::get('mail_host', config('mail.mailers.smtp.host', ''));
        $this->mail_port         = SystemSetting::get('mail_port', (string) config('mail.mailers.smtp.port', '587'));
        $this->mail_encryption   = SystemSetting::get('mail_encryption', 'tls');
        $this->mail_username     = SystemSetting::get('mail_username', config('mail.mailers.smtp.username', ''));
        $this->mail_password     = SystemSetting::get('mail_password', config('mail.mailers.smtp.password', ''));
        $this->mail_from_address = SystemSetting::get('mail_from_address', config('mail.from.address', ''));
        $this->mail_from_name    = SystemSetting::get('mail_from_name', config('mail.from.name', 'SIGEF'));

        // SMS settings
        $this->sms_provider   = SystemSetting::get('sms_provider', 'telcosms');
        $this->sms_api_url    = SystemSetting::get('sms_api_url', config('services.telcosms.api_url', 'https://telcosms.co.ao/send_message'));
        $this->sms_api_key    = SystemSetting::get('sms_api_key', config('services.telcosms.api_key', ''));
        $this->sms_api_secret = SystemSetting::get('sms_api_secret', '');
        $this->sms_sender_id  = SystemSetting::get('sms_sender_id', 'SIGEF');
        $this->sms_enabled    = (bool) SystemSetting::get('sms_enabled', false);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Tabs::make('communication_tabs')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Servidor SMTP')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Configuração SMTP')
                                    ->description('Configure o servidor de email para envio de notificações')
                                    ->icon('heroicon-o-server-stack')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('mail_mailer')
                                                    ->label('Provedor de Email')
                                                    ->options([
                                                        'smtp' => 'SMTP',
                                                        'log' => 'Log (apenas registo, sem envio)',
                                                    ])
                                                    ->default('smtp')
                                                    ->required()
                                                    ->native(false)
                                                    ->live()
                                                    ->helperText('Selecione "SMTP" para enviar emails reais.'),

                                                Forms\Components\TextInput::make('mail_host')
                                                    ->label('Host SMTP')
                                                    ->placeholder('Ex: smtp.gmail.com')
                                                    ->required(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp')
                                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('mail_port')
                                                    ->label('Porta')
                                                    ->placeholder('587')
                                                    ->numeric()
                                                    ->default('587')
                                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp'),

                                                Forms\Components\Select::make('mail_encryption')
                                                    ->label('Encriptação')
                                                    ->options([
                                                        'tls' => 'TLS (Recomendado)',
                                                        'ssl' => 'SSL',
                                                        '' => 'Nenhuma',
                                                    ])
                                                    ->default('tls')
                                                    ->native(false)
                                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp'),

                                                Forms\Components\TextInput::make('mail_username')
                                                    ->label('Utilizador / Email')
                                                    ->placeholder('Ex: noreply@sigef.ao')
                                                    ->maxLength(255)
                                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp'),

                                                Forms\Components\TextInput::make('mail_password')
                                                    ->label('Password')
                                                    ->password()
                                                    ->revealable()
                                                    ->maxLength(255)
                                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('mail_mailer') === 'smtp'),
                                            ]),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Remetente')
                                    ->description('Configurações do remetente de email')
                                    ->icon('heroicon-o-user-circle')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('mail_from_address')
                                                    ->label('Email do Remetente')
                                                    ->placeholder('noreply@sigef.ao')
                                                    ->email()
                                                    ->required()
                                                    ->suffixIcon('heroicon-o-envelope')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('mail_from_name')
                                                    ->label('Nome do Remetente')
                                                    ->placeholder('SIGEF')
                                                    ->default('SIGEF')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Servidor SMS')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Configuração da API de SMS')
                                    ->description('Configure a API de SMS para envio de mensagens')
                                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                    ->schema([
                                        Forms\Components\Toggle::make('sms_enabled')
                                            ->label('Serviço SMS Activo')
                                            ->helperText('Activar/desactivar o envio de SMS')
                                            ->live()
                                            ->columnSpanFull(),

                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('sms_provider')
                                                    ->label('Provedor de SMS')
                                                    ->options([
                                                        'telcosms' => 'TelcoSMS (Angola)',
                                                        'custom' => 'API Personalizada',
                                                        'twilio' => 'Twilio',
                                                        'nexmo' => 'Vonage (Nexmo)',
                                                        'africas_talking' => 'Africa\'s Talking',
                                                        'infobip' => 'Infobip',
                                                    ])
                                                    ->default('telcosms')
                                                    ->required()
                                                    ->native(false)
                                                    ->live()
                                                    ->helperText('Selecione o provedor de SMS a utilizar.'),

                                                Forms\Components\TextInput::make('sms_sender_id')
                                                    ->label('Sender ID / Remetente')
                                                    ->placeholder('SIGEF')
                                                    ->default('SIGEF')
                                                    ->maxLength(11)
                                                    ->helperText('Nome que aparece como remetente (máx. 11 caracteres)'),

                                                Forms\Components\TextInput::make('sms_api_url')
                                                    ->label('URL da API')
                                                    ->placeholder('https://telcosms.co.ao/send_message')
                                                    ->url()
                                                    ->maxLength(500)
                                                    ->columnSpanFull()
                                                    ->helperText('Endpoint completo da API de envio de SMS'),

                                                Forms\Components\TextInput::make('sms_api_key')
                                                    ->label('API Key / Token')
                                                    ->placeholder('Sua chave de API')
                                                    ->password()
                                                    ->revealable()
                                                    ->maxLength(500),

                                                Forms\Components\TextInput::make('sms_api_secret')
                                                    ->label('API Secret')
                                                    ->placeholder('Segredo da API (se aplicável)')
                                                    ->password()
                                                    ->revealable()
                                                    ->maxLength(500)
                                                    ->helperText('Deixe vazio se o provedor não requer'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->contained(false),
            ]);
    }

    public function save(): void
    {
        // Save SMTP fields
        $smtpFields = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
        ];

        foreach ($smtpFields as $key) {
            SystemSetting::set($key, $this->{$key} ?? '', 'mail');
        }

        // Save SMS fields
        $smsFields = [
            'sms_provider',
            'sms_api_url',
            'sms_api_key',
            'sms_api_secret',
            'sms_sender_id',
        ];

        foreach ($smsFields as $key) {
            SystemSetting::set($key, $this->{$key} ?? '', 'sms');
        }

        SystemSetting::set('sms_enabled', $this->sms_enabled ? '1' : '0', 'sms');

        // Clear config cache so new settings take effect
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Ignore if artisan fails
        }

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();
    }

    public function testConnection(): void
    {
        // Temporarily apply settings for testing
        Config::set('mail.default', $this->mail_mailer ?? 'smtp');
        Config::set('mail.mailers.smtp.host', $this->mail_host ?? '');
        Config::set('mail.mailers.smtp.port', (int) ($this->mail_port ?? 587));
        Config::set('mail.mailers.smtp.username', $this->mail_username ?? '');
        Config::set('mail.mailers.smtp.password', $this->mail_password ?? '');
        Config::set('mail.mailers.smtp.encryption', $this->mail_encryption ?? 'tls');
        // Laravel uses 'smtps' scheme for SSL, and null for TLS (STARTTLS)
        $scheme = match ($this->mail_encryption) {
            'ssl' => 'smtps',
            'tls' => null,
            default => null,
        };
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.from.address', $this->mail_from_address ?? '');
        Config::set('mail.from.name', $this->mail_from_name ?? 'SIGEF');

        // Force recreate the mailer with new config
        app()->forgetInstance('mail.manager');

        try {
            $userEmail = auth()->user()->email;

            Mail::raw(
                "Este é um email de teste do SIGEF.\n\nSe recebeu este email, a configuração do servidor de email está correcta.\n\nData/Hora: " . now()->format('d/m/Y H:i:s'),
                function ($message) use ($userEmail) {
                    $message->to($userEmail)
                        ->subject('SIGEF - Teste de Conexão de Email')
                        ->from($this->mail_from_address ?? 'test@sigef.ao', $this->mail_from_name ?? 'SIGEF');
                }
            );

            Notification::make()
                ->title('Email de teste enviado com sucesso!')
                ->body("Verifique a caixa de entrada de: {$userEmail}")
                ->success()
                ->icon('heroicon-o-check-circle')
                ->duration(8000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao enviar email de teste')
                ->body($e->getMessage())
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->persistent()
                ->send();
        }
    }

    public function testSms(): void
    {
        if (empty($this->sms_api_key)) {
            Notification::make()
                ->title('API Key não configurada')
                ->body('Preencha a API Key antes de testar.')
                ->warning()
                ->send();
            return;
        }

        $userPhone = auth()->user()->phone ?? null;
        if (empty($userPhone)) {
            Notification::make()
                ->title('Sem número de telefone')
                ->body('O seu perfil não tem número de telefone. Adicione um para testar o SMS.')
                ->warning()
                ->send();
            return;
        }

        try {
            $message = 'SIGEF - Teste de SMS. Se recebeu esta mensagem, a configuração está correcta. ' . now()->format('d/m/Y H:i');

            if ($this->sms_provider === 'custom' && !empty($this->sms_api_url)) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->sms_api_key,
                    'Content-Type' => 'application/json',
                ])->post($this->sms_api_url, [
                    'to' => $userPhone,
                    'from' => $this->sms_sender_id ?? 'SIGEF',
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    Notification::make()
                        ->title('SMS de teste enviado!')
                        ->body("Enviado para: {$userPhone}")
                        ->success()
                        ->icon('heroicon-o-check-circle')
                        ->duration(8000)
                        ->send();
                } else {
                    throw new \Exception('Resposta da API: ' . $response->status() . ' - ' . $response->body());
                }
            } else {
                Notification::make()
                    ->title('Provedor não configurado')
                    ->body('Configure o URL da API ou selecione um provedor suportado.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao enviar SMS de teste')
                ->body($e->getMessage())
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->persistent()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
