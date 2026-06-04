<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
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
                // Visible for recipients (always when sent) or sender (when there are responses)
                ->visible(function () use ($isRecipient, $isSender) {
                    if (!$this->record->isSent()) {
                        return false;
                    }
                    // Recipients can always respond to sent documents
                    if ($isRecipient) {
                        return true;
                    }
                    // Sender can respond when there are responses
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
                        ->maxSize(10240) // 10MB
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

                    // For sender responses, we need to find or create a recipient entry
                    $recipient = $this->record->recipients()
                        ->where('user_id', $user->id)
                        ->first();

                    // If user is sender and not a recipient, get the first recipient for the response
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

                        // Notify the other party
                        if ($this->record->sender_user_id === $user->id) {
                            // Sender is responding - notify recipient
                            $recipient->user?->notify(new DocumentResponseNotification($response));
                        } else {
                            // Recipient is responding - notify sender
                            $this->record->sender?->notify(new DocumentResponseNotification($response));
                        }

                        Notification::make()
                            ->title('Resposta enviada!')
                            ->success()
                            ->send();

                        // Refresh the page to show the new response
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
        // Mark as read when viewed by recipient
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
        if ($user && $user->can('View:Document')) {
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
