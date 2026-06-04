<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDocumentNotification extends Notification
{

    protected Document $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Resolve the correct document URL based on user's panel access.
     */
    protected function resolveDocumentUrl(object $notifiable): string
    {
        $documentId = $this->document->id;

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
            ->subject('Novo Documento Recebido: ' . $this->document->title)
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line('Recebeu um novo documento de ' . $this->document->senderInstitution->name)
            ->line('Assunto: ' . $this->document->title)
            ->line('Prioridade: ' . Document::getPriorityOptions()[$this->document->priority])
            ->action('Ver Documento', url($this->resolveDocumentUrl($notifiable)))
            ->line('Obrigado por usar o SIGEF!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $priorityColors = [
            'normal' => 'primary',
            'urgent' => 'warning',
            'confidential' => 'danger',
        ];

        $priorityIcons = [
            'normal' => 'heroicon-o-document-text',
            'urgent' => 'heroicon-o-exclamation-triangle',
            'confidential' => 'heroicon-o-lock-closed',
        ];

        $url = $this->resolveDocumentUrl($notifiable);

        return [
            'title' => 'Novo Documento Recebido',
            'body' => $this->document->title . ' - de ' . $this->document->senderInstitution->name,
            'icon' => $priorityIcons[$this->document->priority] ?? 'heroicon-o-document-text',
            'color' => $priorityColors[$this->document->priority] ?? 'primary',
            'document_id' => $this->document->id,
            'sender_institution' => $this->document->senderInstitution->name,
            'priority' => $this->document->priority,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Documento',
                    'url' => $url,
                    'color' => 'primary',
                ],
            ],
        ];
    }

    /**
     * Get the Filament database notification representation
     * This format is required for notifications to appear in Filament's notification bell
     */
    public function toDatabase(object $notifiable): array
    {
        $url = $this->resolveDocumentUrl($notifiable);

        return \Filament\Notifications\Notification::make()
            ->title('Novo Documento Recebido')
            ->body($this->document->title . ' - de ' . $this->document->senderInstitution->name)
            ->icon('heroicon-o-document-text')
            ->success()
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Ver Documento')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
