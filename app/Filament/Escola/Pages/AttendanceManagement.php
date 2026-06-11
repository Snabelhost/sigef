<?php

namespace App\Filament\Escola\Pages;

use App\Models\Attendance;
use App\Models\Effective;
use App\Models\Student;
use App\Models\Institution;
use App\Models\Trainer;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class AttendanceManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?string $navigationLabel = 'Presenças';
    protected static ?string $title = 'Gestão de Presenças';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 9;
    protected string $view = 'filament.pages.attendance-management';

    // Propriedades públicas para Livewire
    public string $activeTab = 'students';
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

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function updatedSelectedInstitutionId(): void
    {
        $this->selectedCia = null;
        $this->attendanceData = [];
        $this->loadAttendanceData();
    }

    public function updatedSelectedCia(): void
    {
        $this->loadAttendanceData();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadAttendanceData();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['students', 'trainers', 'effectives'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->attendanceData = [];
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
    public function trainers(): Collection
    {
        if (!$this->selectedInstitutionId) {
            return collect();
        }

        return Trainer::query()
            ->with('rank')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->where('institution_id', $this->selectedInstitutionId)
                    ->orWhereHas('institutions', fn ($institutionQuery) => $institutionQuery->whereKey($this->selectedInstitutionId));
            })
            ->orderBy('full_name')
            ->get();
    }

    #[Computed]
    public function effectives(): Collection
    {
        if (!$this->selectedInstitutionId) {
            return collect();
        }

        return Effective::query()
            ->where('institution_id', $this->selectedInstitutionId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    #[Computed]
    public function attendancePeople(): Collection
    {
        return match ($this->activeTab) {
            'trainers' => $this->trainers,
            'effectives' => $this->effectives,
            default => $this->students,
        };
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
        if (!$this->selectedInstitutionId || !$this->selectedDate || ($this->activeTab === 'students' && !$this->selectedCia)) {
            $this->attendanceData = [];
            return;
        }

        $people = $this->attendancePeople;
        $foreignKey = $this->attendanceForeignKey();

        // Carregar presenças do dia selecionado
        $attendances = Attendance::where('institution_id', $this->selectedInstitutionId)
            ->where('date', $this->selectedDate)
            ->whereIn($foreignKey, $people->pluck('id'))
            ->get()
            ->keyBy($foreignKey);

        $this->attendanceData = [];

        foreach ($people as $person) {
            $attendance = $attendances->get($person->id);
            $this->attendanceData[$person->id] = [
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

    public function setStatus(int $personId, string $status): void
    {
        if (!$this->validateDateRestrictions($status)) return;

        Attendance::updateOrCreate(
            $this->attendanceLookup($personId),
            array_merge($this->attendanceActorValues($personId), [
                'status' => $status,
                'entry_time' => $status === 'P' ? ($this->attendanceData[$personId]['entry_time'] ?? '08:00') : null,
                'created_by' => Auth::id(),
            ])
        );

        $this->attendanceData[$personId]['status'] = $status;

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

    public function updateEntryTime(int $personId, string $time): void
    {
        if (empty($time)) return;
        if (!$this->validateTodayOnly()) return;

        Attendance::updateOrCreate(
            $this->attendanceLookup($personId),
            array_merge($this->attendanceActorValues($personId), [
                'entry_time' => $time,
                'status' => $this->attendanceData[$personId]['status'] ?? 'P',
                'created_by' => Auth::id(),
            ])
        );

        $this->attendanceData[$personId]['entry_time'] = $time;
    }

    public function updateExitTime(int $personId, string $time): void
    {
        if (empty($time)) return;
        if (!$this->validateTodayOnly()) return;

        Attendance::where($this->attendanceLookup($personId))->update(['exit_time' => $time]);

        $this->attendanceData[$personId]['exit_time'] = $time;
    }

    public function updateObservation(int $personId, ?string $observation): void
    {
        if (!$this->validateTodayOnly()) return;

        $observation = trim((string) $observation);

        Attendance::updateOrCreate(
            $this->attendanceLookup($personId),
            array_merge($this->attendanceActorValues($personId), [
                'observation' => $observation !== '' ? $observation : null,
                'status' => $this->attendanceData[$personId]['status'] ?? 'P',
                'created_by' => Auth::id(),
            ])
        );

        $this->attendanceData[$personId]['observation'] = $observation;
    }

    public function markAllPresent(): void
    {
        if (!$this->validateTodayOnly()) return;

        $people = $this->attendancePeople;
        $count = 0;

        foreach ($people as $person) {
            if (empty($this->attendanceData[$person->id]['status'])) {
                Attendance::updateOrCreate(
                    $this->attendanceLookup($person->id),
                    array_merge($this->attendanceActorValues($person->id), [
                        'status' => 'P',
                        'entry_time' => '08:00',
                        'created_by' => Auth::id(),
                    ])
                );
                $this->attendanceData[$person->id]['status'] = 'P';
                $this->attendanceData[$person->id]['entry_time'] = '08:00';
                $count++;
            }
        }

        $this->loadAttendanceData();

        Notification::make()
            ->title("{$count} {$this->attendanceCollectionLabel()} marcados como presente")
            ->success()
            ->send();
    }

    public function markAllAbsent(): void
    {
        if (!$this->validateTodayOnly()) return;

        $people = $this->attendancePeople;
        $count = 0;

        foreach ($people as $person) {
            if (empty($this->attendanceData[$person->id]['status'])) {
                Attendance::updateOrCreate(
                    $this->attendanceLookup($person->id),
                    array_merge($this->attendanceActorValues($person->id), [
                        'status' => 'F',
                        'created_by' => Auth::id(),
                    ])
                );
                $this->attendanceData[$person->id]['status'] = 'F';
                $count++;
            }
        }

        $this->loadAttendanceData();

        Notification::make()
            ->title("{$count} {$this->attendanceCollectionLabel()} marcados como falta")
            ->warning()
            ->send();
    }

    public function clearAll(): void
    {
        if (!$this->validateTodayOnly()) return;

        $people = $this->attendancePeople;

        Attendance::where('institution_id', $this->selectedInstitutionId)
            ->where('date', $this->selectedDate)
            ->whereIn($this->attendanceForeignKey(), $people->pluck('id'))
            ->delete();

        $this->loadAttendanceData();

        Notification::make()
            ->title('Todos os registos foram removidos')
            ->info()
            ->send();
    }

    protected function attendanceForeignKey(): string
    {
        return match ($this->activeTab) {
            'trainers' => 'trainer_id',
            'effectives' => 'effective_id',
            default => 'student_id',
        };
    }

    protected function attendanceLookup(int $personId): array
    {
        return [
            $this->attendanceForeignKey() => $personId,
            'institution_id' => $this->selectedInstitutionId,
            'date' => $this->selectedDate,
            'period' => 'full_day',
        ];
    }

    protected function attendanceActorValues(int $personId): array
    {
        return [
            'student_id' => $this->activeTab === 'students' ? $personId : null,
            'trainer_id' => $this->activeTab === 'trainers' ? $personId : null,
            'effective_id' => $this->activeTab === 'effectives' ? $personId : null,
            'institution_id' => $this->selectedInstitutionId,
            'date' => $this->selectedDate,
            'period' => 'full_day',
        ];
    }

    protected function attendanceCollectionLabel(): string
    {
        return match ($this->activeTab) {
            'trainers' => 'professores',
            'effectives' => 'efectivos',
            default => 'formandos',
        };
    }

    public function activeTabLabel(): string
    {
        return match ($this->activeTab) {
            'trainers' => 'Professores',
            'effectives' => 'Efectivos',
            default => 'Formandos',
        };
    }

    public function personName(mixed $person): string
    {
        return trim((string) ($person->full_name ?? $person->candidate?->full_name ?? '-')) ?: '-';
    }

    public function personInitials(mixed $person): string
    {
        $name = $this->personName($person);
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? 'S';
        $last = $parts[count($parts) - 1] ?? '';
        $initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));

        return mb_strlen($initials) >= 2 ? $initials : mb_strtoupper(mb_substr($name, 0, 2));
    }

    public function personIdentifier(mixed $person): string
    {
        $identifier = match ($this->activeTab) {
            'trainers' => $person->nip ?: $person->bilhete,
            'effectives' => $person->identifier ?: $person->employee_number ?: $person->nas ?: $person->document_number,
            default => $person->nuri ?: $person->candidate?->nuri ?: $person->student_number,
        };

        return trim((string) $identifier) ?: '-';
    }

    public function personContext(mixed $person): string
    {
        return match ($this->activeTab) {
            'trainers' => trim((string) ($person->rank?->name ?: $person->trainer_type ?: $person->department ?: '-')) ?: '-',
            'effectives' => trim((string) ($person->position_label ?: $person->unit ?: $person->department ?: '-')) ?: '-',
            default => trim((string) ($person->student_type ?: $person->rank?->name ?: '-')) ?: '-',
        };
    }

    public function contextIsReady(): bool
    {
        return filled($this->selectedInstitutionId)
            && filled($this->selectedDate)
            && ($this->activeTab !== 'students' || filled($this->selectedCia));
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
