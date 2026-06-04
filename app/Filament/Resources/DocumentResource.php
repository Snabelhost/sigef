<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Institution;
use App\Models\User;
use App\Notifications\NewDocumentNotification;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    /**
     * Disable automatic tenant scoping so we can show documents
     * from other institutions when user is a recipient
     */
    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static string|\UnitEnum|null $navigationGroup = 'Comunicação';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Documentos';
    protected static ?string $modelLabel = 'Documento';
    protected static ?string $pluralModelLabel = 'Documentos';

    /**
     * Override base query to show documents the user sent OR received
     * This bypasses the default tenancy scoping which only shows documents
     * where sender_institution_id matches the current tenant
     */
    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();

        // Use withoutGlobalScopes to bypass tenancy filtering
        // This allows showing documents from other institutions when user is a recipient
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where(function (Builder $query) use ($userId) {
                // Documents the user sent
                $query->where('sender_user_id', $userId)
                    // OR documents the user received
                    ->orWhereHas('recipients', fn(Builder $q) => $q->where('user_id', $userId));
            });
    }

    /**
     * Override record route binding to allow viewing documents without global scopes
     * This ensures the view/edit pages can load documents even when tenancy is bypassed
     */
    public static function resolveRecordRouteBinding(int|string $key, ?\Closure $modifyQuery = null): ?\Illuminate\Database\Eloquent\Model
    {
        $userId = Auth::id();

        return Document::withoutGlobalScopes()
            ->where(function (Builder $query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhereHas('recipients', fn(Builder $q) => $q->where('user_id', $userId));
            })
            ->find($key);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Informações do Documento')
                    ->description('Preencha os dados do documento a enviar')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Assunto')
                                    ->placeholder('Ex: Solicitação de transferência de formandos')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Nº de Referência')
                                    ->placeholder('Ex: OF/DPF/2026/001')
                                    ->maxLength(100)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('priority')
                                    ->label('Prioridade')
                                    ->options(Document::getPriorityOptions())
                                    ->default('normal')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options(Document::getStatusOptions())
                                    ->default('draft')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\RichEditor::make('content')
                            ->label('Conteúdo do Documento')
                            ->placeholder('Escreva aqui o conteúdo do documento...')
                            ->required()
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('documents/inline')
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Destinatários')
                    ->description('Selecione as instituições e utilizadores que receberão este documento')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Repeater::make('recipient_institutions')
                            ->label('Instituições Destinatárias')
                            ->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Instituição')
                                    ->options(function () {
                                        $currentInstitutionId = Auth::user()?->institution_id;
                                        return Institution::where('is_active', true)
                                            ->when($currentInstitutionId, function ($query) use ($currentInstitutionId) {
                                                return $query->where('id', '!=', $currentInstitutionId);
                                            })
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Set $set) => $set('user_ids', [])),

                                Forms\Components\CheckboxList::make('user_ids')
                                    ->label('Utilizadores')
                                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        $institutionId = $get('institution_id');
                                        if (!$institutionId) {
                                            return [];
                                        }
                                        return User::where('institution_id', $institutionId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->columns(2)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->required(),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Instituição')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                isset($state['institution_id'])
                                    ? Institution::find($state['institution_id'])?->name
                                    : null
                            ),
                    ])
                    ->hiddenOn('view'),

                \Filament\Schemas\Components\Section::make('Anexos')
                    ->description('Adicione ficheiros ao documento')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments_upload')
                            ->label('Ficheiros')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(10240) // 10MB
                            ->disk('public')
                            ->directory('documents/attachments')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                            ])
                            ->helperText('Formatos aceites: PDF, Word, Excel, imagens. Máximo 10MB por ficheiro.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Hidden fields for sender info
                Forms\Components\Hidden::make('sender_institution_id')
                    ->default(fn() => Auth::user()?->institution_id ?? \App\Models\Institution::first()?->id),

                Forms\Components\Hidden::make('sender_user_id')
                    ->default(fn() => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Nº Ref.')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Assunto')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Document $record): string => $record->title),

                Tables\Columns\TextColumn::make('senderInstitution.name')
                    ->label('De')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Remetente')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgent' => 'warning',
                        'confidential' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn(string $state): string => Document::getPriorityOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'success',
                        'archived' => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn(string $state): string => Document::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Destinatários')
                    ->counts('recipients')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('attachments_count')
                    ->label('Anexos')
                    ->counts('attachments')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Document::getStatusOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(Document::getPriorityOptions()),

                Tables\Filters\SelectFilter::make('sender_institution_id')
                    ->label('Instituição Remetente')
                    ->relationship('senderInstitution', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('Enviado desde'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('Enviado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Novo Documento')
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),

                    \Filament\Actions\Action::make('respond')
                        ->label('Responder')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('primary')
                        ->visible(function (Document $record): bool {
                            $user = Auth::user();
                            $isRecipient = $record->recipients()->where('user_id', $user?->id)->exists();
                            return $isRecipient && $record->isSent();
                        })
                        ->form([
                            Forms\Components\RichEditor::make('response_content')
                                ->label('Sua Resposta')
                                ->required()
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'bulletList',
                                    'orderedList',
                                    'link',
                                ]),
                            Forms\Components\FileUpload::make('attachments')
                                ->label('Anexos')
                                ->multiple()
                                ->directory('document-responses')
                                ->maxFiles(5)
                                ->maxSize(10240)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                ])
                                ->helperText('Máx. 5 ficheiros, 10MB cada (PDF, Word, Excel, Imagens)')
                                ->columnSpanFull(),
                        ])
                        ->modalWidth('xl')
                        ->modalHeading('Responder ao Documento')
                        ->modalSubmitActionLabel('Enviar Resposta')
                        ->modalCancelActionLabel('Cancelar')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->color('primary')->icon('heroicon-o-paper-airplane'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->color('danger')->icon('heroicon-o-x-mark'))
                        ->action(function (Document $record, array $data): void {
                            $user = Auth::user();
                            $recipient = $record->recipients()
                                ->where('user_id', $user->id)
                                ->first();

                            if ($recipient) {
                                $response = \App\Models\DocumentResponse::create([
                                    'document_id' => $record->id,
                                    'document_recipient_id' => $recipient->id,
                                    'user_id' => $user->id,
                                    'content' => $data['response_content'],
                                    'attachments' => $data['attachments'] ?? null,
                                ]);

                                // Notify the sender
                                $record->sender->notify(new \App\Notifications\DocumentResponseNotification($response));

                                Notification::make()
                                    ->title('Resposta enviada!')
                                    ->success()
                                    ->send();
                            }
                        }),

                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->visible(fn(Document $record): bool => $record->isDraft()),

                    \Filament\Actions\Action::make('send')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Enviar Documento')
                        ->modalDescription('Tem certeza que deseja enviar este documento? Os destinatários serão notificados.')
                        ->modalSubmitActionLabel('Sim, Enviar')
                        ->modalCancelActionLabel('Cancelar')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->color('primary')->icon('heroicon-o-paper-airplane'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->color('danger')->icon('heroicon-o-x-mark'))
                        ->visible(fn(Document $record): bool => $record->isDraft())
                        ->action(function (Document $record) {
                            // Ensure the status is updated first
                            $record->send();

                            // Notificar todos os destinatários - add safety check for null user
                            foreach ($record->recipients as $recipient) {
                                if ($recipient->user) {
                                    try {
                                        $recipient->user->notify(new NewDocumentNotification($record));
                                    } catch (\Exception $e) {
                                        // Skip if notification fails, but don't break the whole process
                                        \Illuminate\Support\Facades\Log::error("Falha ao notificar utilizador {$recipient->user_id}: " . $e->getMessage());
                                    }
                                }
                            }

                            Notification::make()
                                ->title('Documento enviado!')
                                ->body('O documento foi enviado com sucesso para ' . $record->recipients->count() . ' destinatário(s).')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\Action::make('archive')
                        ->label('Arquivar')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Arquivar Documento')
                        ->modalDescription('Tem certeza que deseja arquivar este documento?')
                        ->modalSubmitActionLabel('Confirmar')
                        ->modalCancelActionLabel('Cancelar')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->color('primary')->icon('heroicon-o-archive-box'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->color('danger')->icon('heroicon-o-x-mark'))
                        ->visible(fn(Document $record): bool => $record->isSent())
                        ->action(function (Document $record) {
                            $record->update(['status' => Document::STATUS_ARCHIVED]);

                            Notification::make()
                                ->title('Documento arquivado')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->visible(fn(Document $record): bool => $record->isDraft()),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informações do Documento')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->label('Assunto')
                            ->columnSpan(1),
                        \Filament\Infolists\Components\TextEntry::make('reference_number')
                            ->label('Nº de Referência')
                            ->placeholder('N/A')
                            ->columnSpan(1),
                        \Filament\Infolists\Components\TextEntry::make('priority')
                            ->label('Prioridade')
                            ->badge()
                            ->color(fn(?string $state): string => match ($state) {
                                'urgent' => 'warning',
                                'confidential' => 'danger',
                                default => 'primary',
                            })
                            ->formatStateUsing(fn(?string $state): string => Document::getPriorityOptions()[$state] ?? ($state ?? 'N/A')),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn(?string $state): string => match ($state) {
                                'draft' => 'gray',
                                'sent' => 'success',
                                'archived' => 'warning',
                                default => 'primary',
                            })
                            ->formatStateUsing(fn(?string $state): string => Document::getStatusOptions()[$state] ?? ($state ?? 'N/A')),
                        \Filament\Infolists\Components\TextEntry::make('senderInstitution.name')
                            ->label('Instituição Remetente')
                            ->icon('heroicon-o-building-office'),
                        \Filament\Infolists\Components\TextEntry::make('sender.name')
                            ->label('Enviado por')
                            ->icon('heroicon-o-user'),
                        \Filament\Infolists\Components\TextEntry::make('sent_at')
                            ->label('Enviado em')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-clock')
                            ->placeholder('Rascunho'),
                    ]),

                \Filament\Schemas\Components\Section::make('Conteúdo')
                    ->icon('heroicon-o-document')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('content')
                            ->label('')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Anexos')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('attachments')
                            ->label('')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('original_name')
                                            ->label('Ficheiro')
                                            ->icon(fn($record) => $record?->icon ?? 'heroicon-o-paper-clip'),
                                        \Filament\Infolists\Components\TextEntry::make('formatted_size')
                                            ->label('Tamanho'),
                                        \Filament\Infolists\Components\TextEntry::make('file_path')
                                            ->label('Download')
                                            ->formatStateUsing(fn($state) => 'Baixar')
                                            ->url(fn($record) => $record ? \Illuminate\Support\Facades\Storage::url($record->file_path) : null)
                                            ->openUrlInNewTab()
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ])
                    ->visible(fn($record) => $record && $record->attachments->count() > 0)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Conversação')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Histórico de respostas e discussão')
                    ->headerActions([
                        \Filament\Actions\Action::make('respond_inline')
                            ->label('Responder')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->color('primary')
                            ->visible(function ($record) {
                                if (!$record || !$record->isSent()) {
                                    return false;
                                }
                                $userId = \Illuminate\Support\Facades\Auth::id();
                                $isRecipient = $record->recipients()->where('user_id', $userId)->exists();
                                $isSender = $record->sender_user_id === $userId;
                                // Recipients can always respond, sender can respond when there are responses
                                return $isRecipient || ($isSender && $record->responses()->exists());
                            })
                            ->form([
                                \Filament\Forms\Components\RichEditor::make('response_content')
                                    ->label('Sua Resposta')
                                    ->required()
                                    ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link']),
                                \Filament\Forms\Components\FileUpload::make('attachments')
                                    ->label('Anexos')
                                    ->multiple()
                                    ->directory('document-responses')
                                    ->maxFiles(5)
                                    ->maxSize(10240)
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'image/jpeg',
                                        'image/png',
                                        'image/gif',
                                    ])
                                    ->helperText('Máx. 5 ficheiros, 10MB cada (PDF, Word, Excel, Imagens)')
                                    ->columnSpanFull(),
                            ])
                            ->modalWidth('xl')
                            ->modalHeading('Responder ao Documento')
                            ->modalSubmitActionLabel('Enviar Resposta')
                            ->modalCancelActionLabel('Cancelar')
                            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->color('primary')->icon('heroicon-o-paper-airplane'))
                            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->color('danger')->icon('heroicon-o-x-mark'))
                            ->action(function (array $data, $record): void {
                                $user = \Illuminate\Support\Facades\Auth::user();

                                $recipient = $record->recipients()->where('user_id', $user->id)->first();
                                if (!$recipient && $record->sender_user_id === $user->id) {
                                    $recipient = $record->recipients()->first();
                                }

                                if ($recipient) {
                                    $response = \App\Models\DocumentResponse::create([
                                        'document_id' => $record->id,
                                        'document_recipient_id' => $recipient->id,
                                        'user_id' => $user->id,
                                        'content' => $data['response_content'],
                                        'attachments' => $data['attachments'] ?? null,
                                    ]);

                                    // Notify the other party
                                    if ($record->sender_user_id === $user->id) {
                                        $recipient->user?->notify(new \App\Notifications\DocumentResponseNotification($response));
                                    } else {
                                        $record->sender?->notify(new \App\Notifications\DocumentResponseNotification($response));
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Resposta enviada!')
                                        ->success()
                                        ->send();
                                }
                            })
                            // Redirect to same page after success to refresh timeline
                            ->successRedirectUrl(fn($record) => \App\Filament\Resources\DocumentResource::getUrl('view', ['record' => $record])),
                    ])
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('responses')
                            ->label('')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(1)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('user.name')
                                            ->label('')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                            ->icon('heroicon-o-user-circle')
                                            ->suffix(fn($record) => ' • ' . ($record?->created_at?->format('d/m/Y H:i') ?? '')),
                                        \Filament\Infolists\Components\TextEntry::make('content')
                                            ->label('')
                                            ->html()
                                            ->columnSpanFull(),
                                        \Filament\Infolists\Components\TextEntry::make('attachments')
                                            ->label('Anexos')
                                            ->icon('heroicon-o-paper-clip')
                                            ->visible(fn($record) => $record && !empty($record->attachments))
                                            ->formatStateUsing(function ($state, $record) {
                                                if (empty($record->attachments) || !is_array($record->attachments)) {
                                                    return null;
                                                }
                                                $links = [];
                                                foreach ($record->attachments as $path) {
                                                    $filename = basename($path);
                                                    $url = \Illuminate\Support\Facades\Storage::url($path);
                                                    $links[] = '<a href="' . $url . '" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);border-radius:6px;color:#3b82f6;font-size:0.8rem;text-decoration:none;margin-right:6px;margin-bottom:4px;">'
                                                        . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>'
                                                        . e($filename) . '</a>';
                                                }
                                                return implode(' ', $links);
                                            })
                                            ->html()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->contained(true),
                        \Filament\Infolists\Components\TextEntry::make('no_responses')
                            ->label('')
                            ->default('Ainda não há respostas a este documento.')
                            ->visible(fn($record) => $record && $record->responses->count() === 0)
                            ->icon('heroicon-o-information-circle')
                            ->color('gray'),
                    ])
                    ->visible(fn($record) => $record && $record->isSent())
                    ->collapsed(fn($record) => $record && $record->responses->count() === 0),
            ]);
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return null;
            }

            $unreadCount = \App\Models\DocumentRecipient::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();

            return $unreadCount > 0 ? (string) $unreadCount : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Allow if has permission
        if ($user->can('ViewAny:Document')) {
            return true;
        }

        // Allow if user has sent any documents
        if (\App\Models\Document::where('sender_user_id', $user->id)->exists()) {
            return true;
        }

        // Allow if user is a recipient of any document
        return \App\Models\DocumentRecipient::where('user_id', $user->id)->exists();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
