<?php

namespace App\Filament\Escola\Resources\DocumentResource\Pages;

use App\Filament\Escola\Resources\DocumentResource;
use App\Models\DocumentAttachment;
use App\Models\DocumentRecipient;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static ?string $title = 'Novo Documento';

    protected ?string $heading = 'Novo Documento';
    protected ?string $subheading = 'Criar e enviar um novo documento';

    public static function getNavigationIcon(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-document-plus';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Documento criado com sucesso!';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->icon('heroicon-o-plus-circle'),
            $this->getCancelFormAction()
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure sender info is set
        $userInstitutionId = Auth::user()?->institution_id;
        $fallbackInstitutionId = \App\Models\Institution::first()?->id;

        $data['sender_institution_id'] = $data['sender_institution_id'] ?? $userInstitutionId ?? $fallbackInstitutionId;
        $data['sender_user_id'] = $data['sender_user_id'] ?? Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->data;

        // Create recipients
        if (isset($data['recipient_institutions']) && is_array($data['recipient_institutions'])) {
            foreach ($data['recipient_institutions'] as $institutionData) {
                $institutionId = $institutionData['institution_id'] ?? null;
                $userIds = $institutionData['user_ids'] ?? [];

                if ($institutionId && !empty($userIds)) {
                    foreach ($userIds as $userId) {
                        DocumentRecipient::create([
                            'document_id' => $record->id,
                            'institution_id' => $institutionId,
                            'user_id' => $userId,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Create attachments
        if (isset($data['attachments_upload']) && is_array($data['attachments_upload'])) {
            foreach ($data['attachments_upload'] as $filePath) {
                $fullPath = Storage::disk('public')->path($filePath);

                DocumentAttachment::create([
                    'document_id' => $record->id,
                    'file_path' => $filePath,
                    'original_name' => basename($filePath),
                    'mime_type' => file_exists($fullPath) ? mime_content_type($fullPath) : null,
                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                ]);
            }
        }

        // Notify user
        $recipientCount = $record->recipients()->count();
        if ($recipientCount > 0) {
            Notification::make()
                ->title('Destinatários adicionados')
                ->body("O documento foi criado com {$recipientCount} destinatário(s). Clique em 'Enviar' para notificá-los.")
                ->info()
                ->persistent()
                ->send();
        }
    }
}
