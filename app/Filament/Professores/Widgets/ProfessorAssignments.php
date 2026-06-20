<?php

namespace App\Filament\Professores\Widgets;

use App\Models\Trainer;
use App\Models\TrainerClassAssignment;
use App\Models\TrainerSubjectAuthorization;
use Filament\Widgets\Widget;

class ProfessorAssignments extends Widget
{
    protected string $view = 'filament.professores.widgets.professor-assignments';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:ProfessorAssignments') ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $isAdmin = (bool) $user?->hasRole('professores_admin');
        $trainer = $isAdmin ? null : $this->currentTrainer();

        return [
            'isAdmin' => $isAdmin,
            'trainer' => $trainer,
            'assignments' => $this->assignments($trainer, $isAdmin),
            'authorizations' => $this->authorizations($trainer, $isAdmin),
        ];
    }

    protected function assignments(?Trainer $trainer, bool $isAdmin): array
    {
        if (! $isAdmin && ! $trainer) {
            return [];
        }

        return TrainerClassAssignment::query()
            ->with([
                'trainer',
                'studentClass.academicYear',
                'studentClass.courseMap.course',
                'subject',
                'academicYear',
            ])
            ->when(! $isAdmin, fn ($query) => $query->where('trainer_id', $trainer?->id))
            ->where('is_active', true)
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (TrainerClassAssignment $assignment): array => [
                'trainer' => $assignment->trainer?->full_name ?: '-',
                'class' => $assignment->studentClass?->name ?: '-',
                'course' => $assignment->studentClass?->courseMap?->course?->name ?: '-',
                'subject' => $assignment->subject?->name ?: '-',
                'academic_year' => $assignment->academicYear?->year
                    ?: $assignment->studentClass?->academicYear?->year
                    ?: '-',
                'schedule' => trim(collect([
                    $assignment->day_of_week,
                    $assignment->start_time && $assignment->end_time
                        ? $assignment->start_time . ' - ' . $assignment->end_time
                        : null,
                ])->filter()->implode(' | ')) ?: '-',
            ])
            ->all();
    }

    protected function authorizations(?Trainer $trainer, bool $isAdmin): array
    {
        if (! $isAdmin && ! $trainer) {
            return [];
        }

        return TrainerSubjectAuthorization::query()
            ->with(['trainer', 'institution', 'course', 'subject'])
            ->when(! $isAdmin, fn ($query) => $query->where('trainer_id', $trainer?->id))
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (TrainerSubjectAuthorization $authorization): array => [
                'trainer' => $authorization->trainer?->full_name ?: '-',
                'institution' => $authorization->institution?->name ?: '-',
                'course' => $authorization->course?->name ?: '-',
                'subject' => $authorization->subject?->name ?: '-',
            ])
            ->all();
    }

    protected function currentTrainer(): ?Trainer
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $trainer = Trainer::query()
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
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }
}
