<?php

namespace App\Filament\Escola\Resources\DocumentResource\Pages;

use App\Filament\Escola\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentResponse;
use App\Models\DocumentRecipient;
use App\Notifications\DocumentResponseNotification;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;
    protected string $view = 'filament.pages.view-document';

    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Visualizar Documento';
    }

    protected function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'refresh' => '$refresh',
        ]);
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $isRecipient = $this->record->recipients()->where('user_id', $user?->id)->exists();
        $isSender = $this->record->sender_user_id === $user?->id;

        return [
            Actions\Action::make('respond')
                ->label('Responder')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary')
                ->visible(function () use ($isRecipient, $isSender) {
                    if (!$this->record->isSent()) {
                        return false;
                    }
                    if ($isRecipient) {
                        return true;
                    }
                    if ($isSender && $this->record->responses()->exists()) {
                        return true;
                    }
                    return false;
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
                ->modalSubmitAction(fn(Actions\Action $action) => $action->color('primary')->icon('heroicon-o-paper-airplane'))
                ->modalCancelAction(fn(Actions\Action $action) => $action->color('danger')->icon('heroicon-o-x-mark'))
                ->action(function (array $data): void {
                    $user = Auth::user();

                    $recipient = $this->record->recipients()
                        ->where('user_id', $user->id)
                        ->first();

                    if (!$recipient && $this->record->sender_user_id === $user->id) {
                        $recipient = $this->record->recipients()->first();
                    }

                    if ($recipient) {
                        $response = DocumentResponse::create([
                            'document_id' => $this->record->id,
                            'document_recipient_id' => $recipient->id,
                            'user_id' => $user->id,
                            'content' => $data['response_content'],
                            'attachments' => $data['attachments'] ?? null,
                        ]);

                        if ($this->record->sender_user_id === $user->id) {
                            $recipient->user?->notify(new DocumentResponseNotification($response));
                        } else {
                            $this->record->sender?->notify(new DocumentResponseNotification($response));
                        }

                        Notification::make()
                            ->title('Resposta enviada!')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    }
                }),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn() => $isSender && $this->record->isDraft()),
        ];
    }

    protected function mutateRecordForView($record)
    {
        $user = Auth::user();
        $recipient = $record->recipients()->where('user_id', $user?->id)->first();

        if ($recipient && !$recipient->isRead()) {
            $recipient->markAsRead();
        }

        return $record;
    }

    /**
     * Override record resolution to bypass tenant scoping for document recipients
     */
    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        // Admin/users with View:Document permission can see all documents
        if ($user && method_exists($user, 'can') && $user->can('View:Document')) {
            return Document::withoutGlobalScopes()->findOrFail($key);
        }

        // Others can only see documents they sent or received
        $userId = Auth::id();

        $record = Document::withoutGlobalScopes()
            ->where(function ($query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhereHas('recipients', fn($q) => $q->where('user_id', $userId));
            })
            ->findOrFail($key);

        return $record;
    }
}
