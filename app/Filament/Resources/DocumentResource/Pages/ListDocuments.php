<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Documento')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();
        $userId = $user?->id;
        $institutionId = $user?->institution_id;

        return [
            'inbox' => Tab::make('Caixa de Entrada')
                ->icon('heroicon-o-inbox')
                ->badge(function () use ($userId) {
                    try {
                        return \App\Models\DocumentRecipient::where('user_id', $userId)
                            ->where('status', 'pending')
                            ->count() ?: null;
                    } catch (\Exception $e) {
                        return null;
                    }
                })
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('recipients', fn ($q) => $q->where('user_id', $userId))
                    ->where('status', 'sent')
                ),

            'sent' => Tab::make('Enviados')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('sender_user_id', $userId)
                    ->where('status', 'sent')
                ),

            'drafts' => Tab::make('Rascunhos')
                ->icon('heroicon-o-document')
                ->badge(function () use ($userId) {
                    try {
                        return \App\Models\Document::where('sender_user_id', $userId)
                            ->where('status', 'draft')
                            ->count() ?: null;
                    } catch (\Exception $e) {
                        return null;
                    }
                })
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('sender_user_id', $userId)
                    ->where('status', 'draft')
                ),

            'archived' => Tab::make('Arquivados')
                ->icon('heroicon-o-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) use ($userId) {
                        $q->where('sender_user_id', $userId)
                          ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $userId));
                    })
                    ->where('status', 'archived')
                ),

            'all' => Tab::make('Todos')
                ->icon('heroicon-o-squares-2x2')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) use ($userId) {
                        $q->where('sender_user_id', $userId)
                          ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $userId));
                    })
                ),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'inbox';
    }
}

