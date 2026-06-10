<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardTemplateResource\Pages;
use App\Models\CardTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CardTemplateResource extends Resource
{
    protected static ?string $model = CardTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cartões';

    protected static ?string $modelLabel = 'Modelo de Cartão';

    protected static ?string $pluralModelLabel = 'Modelos de Cartões';

    protected static ?string $slug = 'configuracoes/card-templates';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('card_type')
            ->orderByDesc('is_default')
            ->orderBy('name');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::cardTemplateFormSchema());
    }

    protected static function cardTemplateFormSchema(): array
    {
        return [
            Section::make('Identificação')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome do modelo')
                        ->required()
                        ->maxLength(191),
                    Forms\Components\Select::make('card_type')
                        ->label('Tipo de cartão')
                        ->options(CardTemplate::cardTypeOptions())
                        ->default(CardTemplate::TYPE_STUDENT)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            static::applyCardTypeDefaults($state, $set);
                        }),
                    Forms\Components\Select::make('card_variant')
                        ->label('Variação do efectivo')
                        ->options(CardTemplate::staffVariantOptions())
                        ->native(false)
                        ->visible(fn (Get $get): bool => in_array($get('card_type'), [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF], true)),
                    Forms\Components\Select::make('style')
                        ->label('Estilo')
                        ->options(CardTemplate::styleOptions())
                        ->default(fn (Get $get): string => static::cardTypeDefaults($get('card_type'))['style'])
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('orientation')
                        ->label('Orientação')
                        ->options(CardTemplate::orientationOptions())
                        ->default(fn (Get $get): string => static::cardTypeDefaults($get('card_type'))['orientation'])
                        ->required()
                        ->native(false),
                    Forms\Components\Toggle::make('is_default')
                        ->label('Modelo padrão')
                        ->helperText('Será usado automaticamente para este tipo de cartão.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])
                ->columnSpanFull(),

            Section::make('Cores')
                ->columns(4)
                ->schema([
                    Forms\Components\ColorPicker::make('primary_color')
                        ->label('Cor primária')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                    Forms\Components\ColorPicker::make('secondary_color')
                        ->label('Cor secundária')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                    Forms\Components\ColorPicker::make('text_color')
                        ->label('Cor do texto')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                    Forms\Components\ColorPicker::make('front_text_color')
                        ->label('Texto da frente'),
                    Forms\Components\ColorPicker::make('header_text_color')
                        ->label('Texto do cabecalho')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                    Forms\Components\ColorPicker::make('back_text_color')
                        ->label('Texto do verso'),
                    Forms\Components\ColorPicker::make('front_background_color')
                        ->label('Fundo da frente'),
                    Forms\Components\ColorPicker::make('back_background_color')
                        ->label('Fundo do verso'),
                ])
                ->columnSpanFull(),

            Section::make('Imagens')
                ->columns(4)
                ->schema([
                    static::imageUpload('logo_path', 'Logo do cartão'),
                    static::imageUpload('front_background_path', 'Fundo da frente'),
                    static::imageUpload('back_background_path', 'Fundo do verso'),
                    static::imageUpload('signature_image_path', 'Imagem da assinatura'),
                    static::imageUpload('fallback_photo_path', 'Foto padrao', 'card-templates/fallback-photos')
                        ->helperText('Usada quando o registo ainda nao tem foto.'),
                ])
                ->columnSpanFull(),

            Section::make('Pré-visualização do modelo')
                ->columns(3)
                ->schema([
                    static::imageUpload('sample_photo_path', 'Foto de exemplo', 'card-templates/samples'),
                    Forms\Components\TextInput::make('sample_payload.name')
                        ->label('Nome de exemplo')
                        ->placeholder('Ex: Betilson Marcas'),
                    Forms\Components\TextInput::make('sample_payload.number')
                        ->label('Número de exemplo')
                        ->placeholder('Ex: 1057728'),
                    Forms\Components\TextInput::make('sample_payload.course')
                        ->label('Curso de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_STUDENT),
                    Forms\Components\TextInput::make('sample_payload.class')
                        ->label('Turma de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_STUDENT),
                    Forms\Components\TextInput::make('sample_payload.academic_degree')
                        ->label('Grau academico de exemplo')
                        ->placeholder('Ex: 1.º ANO')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_PROFESSOR),
                    Forms\Components\TextInput::make('sample_payload.rank')
                        ->label('Posto de exemplo')
                        ->placeholder('Ex: INSPECTOR')
                        ->visible(fn (Get $get): bool => in_array($get('card_type'), [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF], true)),
                    Forms\Components\TextInput::make('sample_payload.discipline')
                        ->label('Disciplina de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_PROFESSOR),
                    Forms\Components\TextInput::make('sample_payload.department')
                        ->label('Departamento de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_PROFESSOR),
                    Forms\Components\TextInput::make('sample_payload.function')
                        ->label('Função de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_STAFF),
                    Forms\Components\TextInput::make('sample_payload.blood_type')
                        ->label('Grupo sanguíneo de exemplo')
                        ->visible(fn (Get $get): bool => $get('card_type') === CardTemplate::TYPE_STAFF),
                ])
                ->columnSpanFull(),

            Section::make('Textos e identidade')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('brand_name')
                        ->label('Nome de marca')
                        ->placeholder('SIGEF'),
                    Forms\Components\TextInput::make('front_title')
                        ->label('Titulo da frente')
                        ->placeholder('PASSE DE IDENTIFICACAO'),
                    Forms\Components\TextInput::make('number_label')
                        ->label('Rotulo do numero')
                        ->placeholder('Nº'),
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Subtítulo'),
                    Forms\Components\TextInput::make('website')
                        ->label('Site'),
                    Forms\Components\TextInput::make('contact_email')
                        ->label('E-mail institucional')
                        ->email(),
                    Forms\Components\TextInput::make('contact_phone')
                        ->label('Telefone institucional'),
                    Forms\Components\TextInput::make('contact_whatsapp')
                        ->label('WhatsApp institucional'),
                    Forms\Components\TextInput::make('address_line')
                        ->label('Morada')
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('back_title')
                        ->label('Título do verso'),
                    Forms\Components\TextInput::make('signature_label')
                        ->label('Legenda da assinatura'),
                    Forms\Components\TextInput::make('signatory_name')
                        ->label('Assinante'),
                    Forms\Components\TextInput::make('signatory_title')
                        ->label('Cargo do assinante'),
                    Forms\Components\Toggle::make('show_qr_code')
                        ->label('Mostrar QR code')
                        ->default(true),
                    Forms\Components\Textarea::make('footer_text')
                        ->label('Texto do verso')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function imageUpload(string $name, string $label, string $directory = 'card-templates'): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(5120)
            ->openable()
            ->downloadable()
            ->previewable()
            ->imagePreviewHeight('150');
    }

    protected static function cardTypeDefaults(?string $cardType): array
    {
        return match ($cardType) {
            CardTemplate::TYPE_PROFESSOR => [
                'style' => CardTemplate::STYLE_STAFF_EFFECTIVE,
                'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                'card_variant' => null,
            ],
            CardTemplate::TYPE_STAFF => [
                'style' => CardTemplate::STYLE_STAFF_EFFECTIVE,
                'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                'card_variant' => 'with_department',
            ],
            default => [
                'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
                'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                'card_variant' => null,
            ],
        };
    }

    protected static function applyCardTypeDefaults(?string $cardType, Set $set): void
    {
        $defaults = static::cardTypeDefaults($cardType);

        $set('style', $defaults['style']);
        $set('orientation', $defaults['orientation']);
        $set('card_variant', $defaults['card_variant']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('card_type')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('card_type_label')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (CardTemplate $record): string => match ($record->card_type) {
                        CardTemplate::TYPE_STUDENT => 'info',
                        CardTemplate::TYPE_PROFESSOR => 'warning',
                        CardTemplate::TYPE_STAFF => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('style')
                    ->label('Estilo')
                    ->formatStateUsing(fn (?string $state): string => CardTemplate::styleOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('orientation')
                    ->label('Orientação')
                    ->formatStateUsing(fn (?string $state): string => CardTemplate::orientationOptions()[$state] ?? (string) $state),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Padrão')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('card_type')
                    ->label('Tipo de cartão')
                    ->options(CardTemplate::cardTypeOptions()),
                Tables\Filters\SelectFilter::make('style')
                    ->label('Estilo')
                    ->options(CardTemplate::styleOptions()),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Modelo padrão'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->headerActions([
                Actions\Action::make('create')
                    ->label('Novo Modelo de Cartão')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => static::getUrl('create')),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    static::previewAction(),
                    Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn (CardTemplate $record): string => static::getUrl('edit', ['record' => $record])),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function previewAction(): Actions\Action
    {
        return Actions\Action::make('preview')
            ->label('Pré-visualizar Cartão')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->modalHeading('Pré-visualização do Cartão')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Actions\Action $action) => $action->label('Fechar Pré-visualização')->icon('heroicon-o-x-mark')->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(fn (CardTemplate $record) => view('cards.print-modal', [
                'template' => $record,
                'payload' => static::samplePayload($record),
                'viewerId' => 'sigef-template-card-viewer-'.$record->getKey(),
                'entityLabel' => 'Modelos',
                'documentName' => 'Modelo - '.$record->name,
                'statusLabel' => 'PRÉ-VISUALIZAÇÃO',
                'statusColor' => 'info',
            ]));
    }

    public static function samplePayload(CardTemplate $template): array
    {
        if ($template->card_type === CardTemplate::TYPE_STUDENT) {
            return app(\App\Services\StudentCardService::class)->templatePreviewPayload($template);
        }

        $sample = $template->sample_payload ?? [];
        $type = $template->card_type;
        $isGeneralStaff = $type === CardTemplate::TYPE_STAFF
            && (
                ($template->card_variant ?? null) === 'civil'
                || str_contains(strtolower((string) ($sample['regime'] ?? '')), 'geral')
                || strtoupper((string) ($sample['document_label'] ?? '')) === 'BI'
            );
        $isGeneralTrainer = $type === CardTemplate::TYPE_PROFESSOR
            && (
                str_contains(strtolower((string) ($sample['regime'] ?? '')), 'geral')
                || strtoupper((string) ($sample['document_label'] ?? '')) === 'BI'
            );
        $name = $sample['name'] ?? match ($type) {
            CardTemplate::TYPE_PROFESSOR => 'Formador Marcas',
            CardTemplate::TYPE_STAFF => 'Antonio Morgado Kidimakaji da Silva',
            default => 'Betilson Marcas',
        };
        $documentNumber = $sample['document_number'] ?? $sample['number'] ?? match ($type) {
            CardTemplate::TYPE_PROFESSOR => $isGeneralTrainer ? '007397943LA048' : '1057728',
            CardTemplate::TYPE_STAFF => $isGeneralStaff ? '007397943LA048' : '1057728',
            default => 'ALN-001',
        };
        $codes = app(\App\Services\CardCodeService::class);
        $verificationUrl = url('/admin/configuracoes/card-templates/'.$template->getKey().'/preview');

        return [
            'name' => $name,
            'number' => $documentNumber,
            'card_number' => $sample['card_number'] ?? $documentNumber,
            'document_label' => $sample['document_label'] ?? match ($type) {
                CardTemplate::TYPE_PROFESSOR => $isGeneralTrainer ? 'BI' : 'NIP',
                CardTemplate::TYPE_STAFF => $isGeneralStaff ? 'BI' : 'NIP',
                default => 'Nº',
            },
            'document_number' => $documentNumber,
            'entity_title' => $sample['entity_title'] ?? match ($type) {
                CardTemplate::TYPE_PROFESSOR => 'FORMADOR',
                CardTemplate::TYPE_STAFF => 'EFECTIVO',
                default => 'SIGEF',
            },
            'logo_url' => $template->logo_url ?: asset('images/logo-policia.png'),
            'institution_name' => $template->brand_name ?: 'SIGEF',
            'institution_location' => $sample['institution_location'] ?? $template->address_line,
            'brand_name' => $template->brand_name ?: 'SIGEF',
            'subtitle' => $template->subtitle,
            'front_title' => $template->front_title ?: match ($type) {
                CardTemplate::TYPE_PROFESSOR => 'CARTÃO DO FORMADOR',
                CardTemplate::TYPE_STAFF => 'CARTÃO DO EFECTIVO',
                default => 'CARTÃO DE IDENTIFICAÇÃO',
            },
            'number_label' => $template->number_label ?: 'Nº',
            'course' => $sample['course'] ?? 'Curso de Ciencias Policiais',
            'class' => $sample['class'] ?? 'A/01',
            'discipline' => $sample['discipline'] ?? 'Direito Penal',
            'department' => $sample['department'] ?? 'Departamento Academico',
            'function' => $sample['function'] ?? ($type === CardTemplate::TYPE_STAFF ? 'ESPECIALISTA' : 'Chefe de Departamento'),
            'blood_type' => $sample['blood_type'] ?? 'O+',
            'regime' => $sample['regime'] ?? ($type === CardTemplate::TYPE_PROFESSOR ? 'REGIME ESPECIAL' : 'Regime Geral'),
            'rank' => $sample['rank'] ?? ($type === CardTemplate::TYPE_STAFF ? 'Civil' : '1.º Subchefe'),
            'position' => $sample['position'] ?? ($type === CardTemplate::TYPE_STAFF ? 'Civil' : '1.º Subchefe'),
            'organ' => $sample['organ'] ?? 'Direcção Académica',
            'placement_organ' => $sample['placement_organ'] ?? 'Direcção Académica',
            'rank' => $type === CardTemplate::TYPE_STAFF ? ($sample['rank'] ?? 'INSPECTOR') : ($sample['rank'] ?? '1. Subchefe'),
            'position' => $type === CardTemplate::TYPE_STAFF ? ($sample['position'] ?? $sample['rank'] ?? 'INSPECTOR') : ($sample['position'] ?? '1. Subchefe'),
            'subjects' => $sample['subjects'] ?? 'Direito Penal, Criminologia',
            'classes' => $sample['classes'] ?? 'A/01, B/09',
            'phone' => $sample['phone'] ?? '+244 921 721 777',
            'email' => $sample['email'] ?? 'exemplo@sigef.local',
            'photo_url' => $template->sample_photo_url,
            'footer_text' => $template->footer_text ?: 'Este cartão identifica o portador no SIGEF.',
            'signature_label' => $template->signature_label,
            'signatory_name' => $template->signatory_name,
            'signatory_title' => $template->signatory_title,
            'signature_url' => $template->signature_image_url,
            'qr_code_uri' => $codes->qrCodeDataUri($verificationUrl),
            'verification_url' => $verificationUrl,
            'show_qr_code' => (bool) ($template->show_qr_code ?? true),
            'show_barcode' => false,
            'is_active' => true,
            'initials' => collect(explode(' ', (string) $name))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => mb_substr($part, 0, 1))
                ->implode(''),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCardTemplates::route('/'),
            'create' => Pages\CreateCardTemplate::route('/create'),
            'edit' => Pages\EditCardTemplate::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CardTemplate') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
