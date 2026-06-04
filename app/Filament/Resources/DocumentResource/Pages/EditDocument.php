<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\DocumentAttachment;
use App\Models\DocumentRecipient;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Documento atualizado com sucesso!';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing recipients for the form
        $recipients = $this->record->recipients()
            ->with('institution', 'user')
            ->get()
            ->groupBy('institution_id');

        $recipientInstitutions = [];
        foreach ($recipients as $institutionId => $institutionRecipients) {
            $recipientInstitutions[] = [
                'institution_id' => $institutionId,
                'user_ids' => $institutionRecipients->pluck('user_id')->toArray(),
            ];
        }

        $data['recipient_institutions'] = $recipientInstitutions;

        // Load existing attachments
        $data['attachments_upload'] = $this->record->attachments->pluck('file_path')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->data;

        // Update recipients - remove old and add new
        if (isset($data['recipient_institutions']) && is_array($data['recipient_institutions'])) {
            // Get new recipient user IDs
            $newRecipientUserIds = collect($data['recipient_institutions'])
                ->flatMap(fn ($inst) => $inst['user_ids'] ?? [])
                ->unique()
                ->toArray();

            // Remove recipients that are no longer selected
            $record->recipients()
                ->whereNotIn('user_id', $newRecipientUserIds)
                ->delete();

            // Add new recipients
            foreach ($data['recipient_institutions'] as $institutionData) {
                $institutionId = $institutionData['institution_id'] ?? null;
                $userIds = $institutionData['user_ids'] ?? [];

                if ($institutionId && !empty($userIds)) {
                    foreach ($userIds as $userId) {
                        DocumentRecipient::firstOrCreate([
                            'document_id' => $record->id,
                            'user_id' => $userId,
                        ], [
                            'institution_id' => $institutionId,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Update attachments
        if (isset($data['attachments_upload']) && is_array($data['attachments_upload'])) {
            $newFilePaths = $data['attachments_upload'];

            // Remove attachments that are no longer in the list
            $existingAttachments = $record->attachments;
            foreach ($existingAttachments as $attachment) {
                if (!in_array($attachment->file_path, $newFilePaths)) {
                    Storage::disk('public')->delete($attachment->file_path);
                    $attachment->delete();
                }
            }

            // Add new attachments
            $existingPaths = $existingAttachments->pluck('file_path')->toArray();
            foreach ($newFilePaths as $filePath) {
                if (!in_array($filePath, $existingPaths)) {
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
        }
    }
}
