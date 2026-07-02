<?php

namespace App\Filament\Professores\Widgets;

use App\Models\Evaluation;
use App\Models\StudentClassEnrollment;
use App\Models\Trainer;
use App\Models\TrainerClassAssignment;
use App\Models\TrainerSubjectAuthorization;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class ProfessorOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected int | array | null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->can('View:ProfessorOverview') ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user?->hasRole('professores_admin')) {
            return $this->adminStats();
        }

        $trainer = $this->currentTrainer();

        if (! $trainer) {
            return [
                Stat::make('Total de Formandos', '0')
                    ->description('Sem formador associado')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('gray'),
                Stat::make('Turmas', '0')
                    ->description('Sem formador associado')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('gray'),
                Stat::make('Disciplinas', '0')
                    ->description('Sem formador associado')
                    ->descriptionIcon('heroicon-m-book-open')
                    ->color('gray'),
                Stat::make('Avaliações', '0')
                    ->description('Sem formador associado')
                    ->descriptionIcon('heroicon-m-document-check')
                    ->color('gray'),
            ];
        }

        $assignmentQuery = TrainerClassAssignment::query()
            ->where('trainer_id', $trainer->id)
            ->where('is_active', true);

        $assignedClassIds = (clone $assignmentQuery)
            ->whereNotNull('class_id')
            ->distinct()
            ->pluck('class_id');

        $assignmentSubjectIds = (clone $assignmentQuery)
            ->whereNotNull('subject_id')
            ->distinct()
            ->pluck('subject_id');

        $authorizedSubjectIds = TrainerSubjectAuthorization::query()
            ->where('trainer_id', $trainer->id)
            ->whereNotNull('subject_id')
            ->distinct()
            ->pluck('subject_id');

        $subjectsCount = $assignmentSubjectIds
            ->merge($authorizedSubjectIds)
            ->filter()
            ->unique()
            ->count();

        $studentsCount = $this->studentsCountForClasses($assignedClassIds);
        $evaluationsCount = $this->evaluationsCount($trainer, $user);

        return [
            Stat::make('Total de Formandos', $studentsCount)
                ->description($trainer->full_name ?: 'Formador vinculado')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Turmas', $assignedClassIds->count())
                ->description('Turmas activas atribuídas')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            Stat::make('Disciplinas', $subjectsCount)
                ->description('Disciplinas autorizadas ou atribuídas')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),
            Stat::make('Avaliações', $evaluationsCount)
                ->description('Registos associados ao formador')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),
        ];
    }

    protected function adminStats(): array
    {
        $activeTrainers = Trainer::query()->where('is_active', true)->count();
        $professorUsers = User::query()->role(['professores_admin', 'professores_user'])->count();
        $assignments = TrainerClassAssignment::query()->where('is_active', true)->count();
        $authorizations = TrainerSubjectAuthorization::query()->count();

        return [
            Stat::make('Formadores Activos', $activeTrainers)
                ->description('Corpo docente registado')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Utilizadores Formadores', $professorUsers)
                ->description('Contas com perfil de formador')
                ->descriptionIcon('heroicon-m-key')
                ->color($professorUsers > 0 ? 'success' : 'warning'),
            Stat::make('Turmas Atribuídas', $assignments)
                ->description('Atribuições activas')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
            Stat::make('Autorizações', $authorizations)
                ->description('Disciplinas autorizadas')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }

    protected function currentTrainer(): ?Trainer
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $trainer = Trainer::query()
            ->with('institution')
            ->where('user_id', $user->id)
            ->first();

        if ($trainer) {
            return $trainer;
        }

        $email = strtolower(trim((string) $user->email));

        if ($email === '') {
            return null;
        }

        return Trainer::query()
            ->with('institution')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    protected function studentsCountForClasses(Collection $classIds): int
    {
        if ($classIds->isEmpty()) {
            return 0;
        }

        return StudentClassEnrollment::query()
            ->whereIn('class_id', $classIds)
            ->where('is_active', true)
            ->distinct()
            ->count('student_id');
    }

    protected function evaluationsCount(Trainer $trainer, ?User $user): int
    {
        return Evaluation::query()
            ->where(function ($query) use ($trainer, $user): void {
                $query
                    ->where('evaluator_name', $trainer->full_name)
                    ->orWhere('evaluated_by', $trainer->id);

                if ($user) {
                    $query->orWhere('evaluated_by', $user->id);
                }
            })
            ->count();
    }
}
