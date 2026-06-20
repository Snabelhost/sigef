<?php

namespace App\Filament\Professores\Resources\EvaluationResource\Pages;

use App\Filament\Professores\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluations extends ListRecords
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Criar Avaliação de Apoio')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalWidth('4xl')
                ->mutateFormDataUsing(fn (array $data): array => EvaluationResource::mutateEvaluationFormData($data))
                ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                ->createAnotherAction(fn (Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                ->createAnother(true)
                ->successNotificationTitle('Registo criado com sucesso!'),
        ];
    }
}
