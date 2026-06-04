<?php

namespace App\Filament\Escola\Resources\StudentLeaveResource\Pages;

use App\Filament\Escola\Resources\StudentLeaveResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use App\Models\StudentLeave;

class ViewStudentLeave extends Page
{
    protected static string $resource = StudentLeaveResource::class;
    
    protected string $view = 'filament.resources.student-leave-resource.pages.view-student-leave';

    public StudentLeave $record;

    public function mount(int | string $record): void
    {
        $this->record = StudentLeave::findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Histórico de Ocorrências - ' . ($this->record->student?->candidate?->full_name ?? 'N/A');
    }
    
    public function getSubheading(): ?string
    {
        return 'Nº de Ordem: ' . ($this->record->student?->student_number ?? 'N/A');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voltar')
                ->label('Voltar à Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(StudentLeaveResource::getUrl('index')),
        ];
    }
    
    public function getOcorrencias()
    {
        return StudentLeave::where('student_id', $this->record->student_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function getTotalDispensas()
    {
        return StudentLeave::where('student_id', $this->record->student_id)
            ->where('leave_type', 'like', 'dispensa_%')
            ->count();
    }
    
    public function getTotalFaltasJustificadas()
    {
        return StudentLeave::where('student_id', $this->record->student_id)
            ->where('leave_type', 'falta_justificada')
            ->count();
    }
    
    public function getTotalFaltasInjustificadas()
    {
        return StudentLeave::where('student_id', $this->record->student_id)
            ->where('leave_type', 'falta_injustificada')
            ->count();
    }
    
    public function getTotalOcorrencias()
    {
        return StudentLeave::where('student_id', $this->record->student_id)->count();
    }
}
