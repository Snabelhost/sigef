<?php

namespace App\Notifications;

use App\Models\DocumentResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentResponseNotification extends Notification
{

    protected DocumentResponse $response;

    /**
     * Create a new notification instance.
     */
    public function __construct(DocumentResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Resolve the correct document URL based on user's panel access.
     */
    protected function resolveDocumentUrl(object $notifiable): string
    {
        $documentId = $this->response->document_id;

        // Check if user belongs to escola panel (has institution_id and escola role)
        if (
            $notifiable->institution_id &&
            ($notifiable->hasRole('escola_admin') || $notifiable->hasRole('panel_user'))
            && !$notifiable->hasRole('super_admin') && !$notifiable->hasRole('admin')
        ) {
            return "/escola/{$notifiable->institution_id}/documents/{$documentId}";
        }

        return "/admin/documents/{$documentId}";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add mail channel if user has email and mail is configured
        if (!empty($notifiable->email) && \App\Models\SystemSetting::isMailConfigured()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Resposta ao Documento: ' . $this->response->document->title)
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line('Recebeu uma resposta ao seu documento.')
            ->line('De: ' . $this->response->user->name)
            ->line('Instituição: ' . $this->response->recipient->institution->name)
            ->action('Ver Resposta', url($this->resolveDocumentUrl($notifiable)))
            ->line('Obrigado por usar o SIGEF!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $url = $this->resolveDocumentUrl($notifiable);

        return [
            'title' => 'Resposta Recebida',
            'body' => $this->response->user->name . ' respondeu ao documento: ' . $this->response->document->title,
            'icon' => 'heroicon-o-chat-bubble-left-ellipsis',
            'color' => 'success',
            'document_id' => $this->response->document_id,
            'response_id' => $this->response->id,
            'responder_name' => $this->response->user->name,
            'responder_institution' => $this->response->recipient->institution->name,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Resposta',
                    'url' => $url,
                    'color' => 'primary',
                ],
            ],
        ];
    }

    /**
     * Get the Filament database notification representation
     */
    public function toDatabase(object $notifiable): array
    {
        $document = $this->response->document;
        $url = $this->resolveDocumentUrl($notifiable);

        return \Filament\Notifications\Notification::make()
            ->title('Resposta Recebida')
            ->body($this->response->user->name . ' respondeu ao documento: ' . $document->title)
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->success()
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Ver Resposta')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
