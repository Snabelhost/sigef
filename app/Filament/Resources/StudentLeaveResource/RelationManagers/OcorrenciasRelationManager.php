<?php

namespace App\Filament\Resources\StudentLeaveResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\StudentLeave;

class OcorrenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'leaves';
    protected static ?string $title = 'Histórico de Ocorrências';
    protected static ?string $modelLabel = 'Ocorrência';
    protected static ?string $pluralModelLabel = 'Ocorrências';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('leave_type')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'dispensa_saude' => 'Dispensa - Saúde',
                        'dispensa_pessoal' => 'Dispensa - Pessoal',
                        'dispensa_servico' => 'Dispensa - Serviço',
                        'dispensa_falecimento' => 'Dispensa - Falecimento',
                        'dispensa_outro' => 'Dispensa - Outro',
                        'falta_justificada' => 'Falta Justificada',
                        'falta_injustificada' => 'Falta Injustificada',
                        'reprovado_faltas' => 'Reprovado Faltas',
                        'reprovado_desistencia' => 'Reprovado Desistência',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'dispensa_saude', 'dispensa_pessoal', 'dispensa_servico', 'dispensa_falecimento', 'dispensa_outro' => 'info',
                        'falta_justificada' => 'warning',
                        'falta_injustificada' => 'danger',
                        'reprovado_faltas', 'reprovado_desistencia' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(30)
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovada',
                        'rejected' => 'Rejeitada',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('novaOcorrencia')
                    ->label('Nova Ocorrência')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('leave_type')
                            ->label('Tipo de Ocorrência')
                            ->options([
                                'dispensa_saude' => 'Dispensa - Saúde',
                                'dispensa_pessoal' => 'Dispensa - Pessoal',
                                'dispensa_servico' => 'Dispensa - Serviço',
                                'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
                                'dispensa_outro' => 'Dispensa - Outro',
                                'falta_justificada' => 'Falta Justificada',
                                'falta_injustificada' => 'Falta Injustificada',
                                'reprovado_faltas' => 'Reprovado por Faltas',
                                'reprovado_desistencia' => 'Reprovado por Desistência',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data de Fim')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo/Justificação')
                            ->rows(3),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendente',
                                'approved' => 'Aprovada',
                                'rejected' => 'Rejeitada',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                    ])
                    ->action(function (array $data): void {
                        StudentLeave::create([
                            'student_id' => $this->ownerRecord->student->id,
                            'institution_id' => $this->ownerRecord->student->institution_id,
                            'leave_type' => $data['leave_type'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                            'reason' => $data['reason'] ?? null,
                            'status' => $data['status'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Ocorrência Registada!')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Registar'))
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('leave_type')
                            ->label('Tipo de Ocorrência')
                            ->options([
                                'dispensa_saude' => 'Dispensa - Saúde',
                                'dispensa_pessoal' => 'Dispensa - Pessoal',
                                'dispensa_servico' => 'Dispensa - Serviço',
                                'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
                                'dispensa_outro' => 'Dispensa - Outro',
                                'falta_justificada' => 'Falta Justificada',
                                'falta_injustificada' => 'Falta Injustificada',
                                'reprovado_faltas' => 'Reprovado por Faltas',
                                'reprovado_desistencia' => 'Reprovado por Desistência',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data de Fim')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo/Justificação')
                            ->rows(3),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendente',
                                'approved' => 'Aprovada',
                                'rejected' => 'Rejeitada',
                            ])
                            ->required()
                            ->native(false),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
