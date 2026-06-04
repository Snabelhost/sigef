<?php

namespace App\Filament\Escola\Pages;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Institution;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class AttendanceManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?string $navigationLabel = 'Pontos / Presenças';
    protected static ?string $title = 'Gestão de Presenças';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 15;
    protected string $view = 'filament.pages.attendance-management';

    // Propriedades públicas para Livewire
    public ?int $selectedInstitutionId = null;
    public ?string $selectedCia = null;
    public ?string $selectedDate = null;
    public array $attendanceData = [];

    public function mount(): void
    {
        $this->selectedDate = Carbon::now()->format('Y-m-d');

        // No painel Escola, usar automaticamente a instituição do tenant
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant) {
            $this->selectedInstitutionId = $tenant->id;
        }
    }

    public function updatedSelectedInstitutionId(): void
    {
        $this->selectedCia = null;
        $this->attendanceData = [];
    }

    public function updatedSelectedCia(): void
    {
        $this->loadAttendanceData();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadAttendanceData();
    }

    #[Computed]
    public function institutions()
    {
        // No painel Escola, mostrar apenas a instituição do tenant
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant) {
            return collect([$tenant->id => $tenant->name]);
        }
        return Institution::orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function cias()
    {
        if (!$this->selectedInstitutionId) {
            return collect();
        }

        // Buscar CIAs únicos dos alunos desta instituição
        return Student::where('institution_id', $this->selectedInstitutionId)
            ->whereNotNull('cia')
            ->where('cia', '!=', '')
            ->distinct()
            ->orderBy('cia')
            ->pluck('cia', 'cia');
    }

    #[Computed]
    public function students()
    {
        if (!$this->selectedInstitutionId || !$this->selectedCia) {
            return collect();
        }

        return Student::where('institution_id', $this->selectedInstitutionId)
            ->where('cia', $this->selectedCia)
            ->with(['candidate', 'rank'])
            ->orderBy('student_number')
            ->get();
    }

    #[Computed]
    public function weekDays()
    {
        $startDate = Carbon::parse($this->selectedDate)->startOfWeek(Carbon::MONDAY);
        $days = [];

        for ($i = 0; $i < 5; $i++) {
            $date = $startDate->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'][$i],
                'day_number' => $date->format('d'),
                'is_today' => $date->isToday(),
                'is_selected' => $date->format('Y-m-d') === $this->selectedDate,
            ];
        }

        return $days;
    }

    public function loadAttendanceData(): void
    {
        if (!$this->selectedInstitutionId || !$this->selectedCia || !$this->selectedDate) {
            $this->attendanceData = [];
            return;
        }

        $students = $this->students;

        // Carregar presenças do dia selecionado
        $attendances = Attendance::where('institution_id', $this->selectedInstitutionId)
            ->where('date', $this->selectedDate)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $this->attendanceData = [];

        foreach ($students as $student) {
            $attendance = $attendances->get($student->id);
            $this->attendanceData[$student->id] = [
                'status' => $attendance?->status ?? null,
                'entry_time' => $attendance?->entry_time ?? null,
                'exit_time' => $attendance?->exit_time ?? null,
                'observation' => $attendance?->observation ?? '',
            ];
        }
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->loadAttendanceData();
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->loadAttendanceData();
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->loadAttendanceData();
    }

    public function goToToday(): void
    {
        $this->selectedDate = Carbon::now()->format('Y-m-d');
        $this->loadAttendanceData();
    }

    public function isToday(): bool
    {
        return $this->selectedDate === Carbon::now()->format('Y-m-d');
    }

    public function isPastDate(): bool
    {
        return Carbon::parse($this->selectedDate)->lt(Carbon::today());
    }

    public function isFutureDate(): bool
    {
        return Carbon::parse($this->selectedDate)->gt(Carbon::today());
    }

    protected function validateDateRestrictions(?string $status = null): bool
    {
        // Datas futuras: bloquear tudo
        if ($this->isFutureDate()) {
            Notification::make()
                ->title('Ação não permitida')
                ->body('Não é possível registar presenças em datas futuras.')
                ->danger()
                ->duration(3000)
                ->send();
            return false;
        }

        // Datas anteriores: permitir apenas justificação (J)
        if ($this->isPastDate() && $status !== 'J') {
            Notification::make()
                ->title('Ação não permitida')
                ->body('Para datas anteriores, apenas é possível justificar faltas (J).')
                ->warning()
                ->duration(3000)
                ->send();
            return false;
        }

        return true;
    }

    protected function validateTodayOnly(): bool
    {
        // Bloquear datas futuras
        if ($this->isFutureDate()) {
            Notification::make()
                ->title('Ação não permitida')
                ->body('Não é possível realizar esta ação em datas futuras.')
                ->danger()
                ->duration(3000)
                ->send();
            return false;
        }

        // Bloquear datas anteriores para ações em massa
        if ($this->isPastDate()) {
            Notification::make()
                ->title('Ação não permitida')
                ->body('Para datas anteriores, apenas é possível justificar faltas individualmente.')
                ->warning()
                ->duration(3000)
                ->send();
            return false;
        }

        return true;
    }

    public function setStatus(int $studentId, string $status): void
    {
        if (!$this->validateDateRestrictions($status)) return;

        $institutionId = $this->selectedInstitutionId;

        Attendance::updateOrCreate(
            [
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'date' => $this->selectedDate,
            ],
            [
                'status' => $status,
                'entry_time' => $status === 'P' ? ($this->attendanceData[$studentId]['entry_time'] ?? '08:00') : null,
                'created_by' => Auth::id(),
            ]
        );

        $this->attendanceData[$studentId]['status'] = $status;

        $statusLabel = match ($status) {
            'P' => 'Presente',
            'F' => 'Falta',
            'J' => 'Justificado',
            'A' => 'Atrasado',
            default => 'Indefinido',
        };

        Notification::make()
            ->title("Marcado como {$statusLabel}")
            ->success()
            ->duration(1500)
            ->send();
    }

    public function updateEntryTime(int $studentId, string $time): void
    {
        if (empty($time)) return;
        if (!$this->validateTodayOnly()) return;

        $institutionId = $this->selectedInstitutionId;

        Attendance::updateOrCreate(
            [
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'date' => $this->selectedDate,
            ],
            [
                'entry_time' => $time,
                'status' => $this->attendanceData[$studentId]['status'] ?? 'P',
                'created_by' => Auth::id(),
            ]
        );

        $this->attendanceData[$studentId]['entry_time'] = $time;
    }

    public function updateExitTime(int $studentId, string $time): void
    {
        if (empty($time)) return;
        if (!$this->validateTodayOnly()) return;

        Attendance::where('student_id', $studentId)
            ->where('institution_id', $this->selectedInstitutionId)
            ->where('date', $this->selectedDate)
            ->update(['exit_time' => $time]);

        $this->attendanceData[$studentId]['exit_time'] = $time;
    }

    public function markAllPresent(): void
    {
        if (!$this->validateTodayOnly()) return;

        $students = $this->students;
        $institutionId = $this->selectedInstitutionId;
        $count = 0;

        foreach ($students as $student) {
            if (empty($this->attendanceData[$student->id]['status'])) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'institution_id' => $institutionId,
                        'date' => $this->selectedDate,
                    ],
                    [
                        'status' => 'P',
                        'entry_time' => '08:00',
                        'created_by' => Auth::id(),
                    ]
                );
                $this->attendanceData[$student->id]['status'] = 'P';
                $this->attendanceData[$student->id]['entry_time'] = '08:00';
                $count++;
            }
        }

        $this->loadAttendanceData();

        Notification::make()
            ->title("{$count} alunos marcados como presente")
            ->success()
            ->send();
    }

    public function markAllAbsent(): void
    {
        if (!$this->validateTodayOnly()) return;

        $students = $this->students;
        $institutionId = $this->selectedInstitutionId;
        $count = 0;

        foreach ($students as $student) {
            if (empty($this->attendanceData[$student->id]['status'])) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'institution_id' => $institutionId,
                        'date' => $this->selectedDate,
                    ],
                    [
                        'status' => 'F',
                        'created_by' => Auth::id(),
                    ]
                );
                $this->attendanceData[$student->id]['status'] = 'F';
                $count++;
            }
        }

        $this->loadAttendanceData();

        Notification::make()
            ->title("{$count} alunos marcados como falta")
            ->warning()
            ->send();
    }

    public function clearAll(): void
    {
        if (!$this->validateTodayOnly()) return;

        $students = $this->students;

        Attendance::where('institution_id', $this->selectedInstitutionId)
            ->where('date', $this->selectedDate)
            ->whereIn('student_id', $students->pluck('id'))
            ->delete();

        $this->loadAttendanceData();

        Notification::make()
            ->title('Todos os registos foram removidos')
            ->info()
            ->send();
    }

    public function getStats(): array
    {
        $total = count($this->attendanceData);
        $present = 0;
        $absent = 0;
        $justified = 0;
        $late = 0;
        $unmarked = 0;

        foreach ($this->attendanceData as $data) {
            match ($data['status'] ?? null) {
                'P' => $present++,
                'F' => $absent++,
                'J' => $justified++,
                'A' => $late++,
                default => $unmarked++,
            };
        }

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'justified' => $justified,
            'late' => $late,
            'unmarked' => $unmarked,
            'percentage' => $total > 0 ? round(($present / $total) * 100) : 0,
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
