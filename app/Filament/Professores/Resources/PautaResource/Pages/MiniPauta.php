<?php

namespace App\Filament\Professores\Resources\PautaResource\Pages;

use App\Filament\Professores\Resources\PautaResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\Trainer;
use App\Models\TrainerClassAssignment;
use App\Models\TrainerSubjectAuthorization;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MiniPauta extends \App\Filament\Resources\PautaResource\Pages\MiniPauta
{
    protected static string $resource = PautaResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($this->currentUserIsProfessorAdmin()) {
            return;
        }

        $this->institution_id = $this->defaultInstitutionId();
        $this->academic_year_id = null;
        $this->course_id = null;
        $this->class_id = null;
        $this->subject_id = null;
        $this->selected_student_id = null;
        $this->showTable = false;
    }

    public function getInstitutions()
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getInstitutions();
        }

        $institutionIds = $this->professorInstitutionIds();

        if ($institutionIds->isEmpty()) {
            return collect();
        }

        return Institution::query()
            ->whereIn('id', $institutionIds)
            ->orderBy('name')
            ->get();
    }

    public function getAcademicYears()
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getAcademicYears();
        }

        $classes = $this->professorClassesForSelectedInstitution()
            ->with('courseMap')
            ->get();

        $academicYearIds = $classes
            ->pluck('academic_year_id')
            ->merge($classes->pluck('courseMap.academic_year_id'))
            ->filter()
            ->unique()
            ->values();

        if ($academicYearIds->isEmpty()) {
            return collect();
        }

        return AcademicYear::query()
            ->whereIn('id', $academicYearIds)
            ->orderByDesc('year')
            ->get();
    }

    public function getCourses()
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getCourses();
        }

        if (! $this->institution_id || ! $this->academic_year_id) {
            return collect();
        }

        $classes = $this->professorClassesForSelectedInstitution()
            ->with('courseMap')
            ->where(function (Builder $query): void {
                $this->whereSelectedAcademicYear($query);
            })
            ->get();

        $courseIds = $classes
            ->pluck('courseMap.course_id')
            ->filter()
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Course::query()
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
    }

    public function getClasses()
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getClasses();
        }

        if (! $this->institution_id || ! $this->academic_year_id || ! $this->course_id) {
            return collect();
        }

        return $this->professorClassesForSelectedInstitution()
            ->with(['institution', 'courseMap.course', 'academicYear'])
            ->where(function (Builder $query): void {
                $this->whereSelectedAcademicYear($query);
            })
            ->whereHas('courseMap', fn (Builder $query): Builder => $query->where('course_id', $this->course_id))
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('student_class_enrollments')
                    ->whereColumn('student_class_enrollments.class_id', 'classes.id')
                    ->where('student_class_enrollments.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function getSubjects()
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getSubjects();
        }

        if (! $this->class_id) {
            return collect();
        }

        $allowedSubjectIds = $this->professorSubjectIdsForClass((int) $this->class_id);

        if ($allowedSubjectIds->isEmpty()) {
            return collect();
        }

        $studentIds = StudentClassEnrollment::query()
            ->where('class_id', $this->class_id)
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return collect();
        }

        $enrolledSubjectIds = StudentSubjectEnrollment::query()
            ->whereIn('student_id', $studentIds)
            ->where('class_id', $this->class_id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        $subjectIds = $enrolledSubjectIds
            ->intersect($allowedSubjectIds)
            ->values();

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get();
    }

    public function getTrainerName(): string
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return parent::getTrainerName();
        }

        return $this->currentTrainer()?->full_name ?? '-';
    }

    public function pesquisar(): void
    {
        if (! $this->professorCanUseSelectedClassSubject()) {
            $this->showTable = false;
            $this->selected_student_id = null;

            return;
        }

        parent::pesquisar();
    }

    public function exportPdf()
    {
        if (! $this->professorCanUseSelectedClassSubject()) {
            return null;
        }

        return parent::exportPdf();
    }

    public function printMiniPautaAction(): Action
    {
        return parent::printMiniPautaAction()
            ->disabled(fn (): bool => ! $this->class_id || ! $this->subject_id || ! $this->professorCanUseSelectedClassSubject());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn () => $this->exportPdf())
                ->disabled(fn (): bool => ! $this->showTable || ! $this->professorCanUseSelectedClassSubject()),
        ];
    }

    protected function saveEvaluation(Student $student, string $type, int $order, $score): void
    {
        $this->selectStudentPhoto($student->id);

        if ($score === null || $score === '' || ! $this->subject_id || ! $this->class_id) {
            return;
        }

        if (! $this->professorCanUseSelectedClassSubject()) {
            return;
        }

        if (! is_numeric($score)) {
            return;
        }

        $isStudentInClass = StudentClassEnrollment::query()
            ->where('student_id', $student->id)
            ->where('class_id', $this->class_id)
            ->where('is_active', true)
            ->exists();

        if (! $isStudentInClass) {
            return;
        }

        $score = max(0, min(20, (float) $score));
        $class = StudentClass::query()->find($this->class_id);
        $trainer = $this->currentTrainer();

        Evaluation::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'subject_id' => $this->subject_id,
                'institution_id' => $class?->institution_id,
                'evaluation_type' => $type,
                'observations' => 'order_' . $order,
            ],
            [
                'score' => $score,
                'evaluated_at' => now(),
                'evaluated_by' => Auth::id(),
                'evaluator_name' => $trainer?->full_name ?: Auth::user()?->name,
            ],
        );
    }

    protected function professorCanUseSelectedClassSubject(): bool
    {
        if ($this->currentUserIsProfessorAdmin()) {
            return true;
        }

        if (! $this->class_id || ! $this->subject_id) {
            return false;
        }

        $trainer = $this->currentTrainer();

        if (! $trainer) {
            return false;
        }

        $hasAssignment = TrainerClassAssignment::query()
            ->where('trainer_id', $trainer->getKey())
            ->where('class_id', $this->class_id)
            ->where('subject_id', $this->subject_id)
            ->where('is_active', true)
            ->exists();

        if ($hasAssignment) {
            return true;
        }

        $class = StudentClass::query()
            ->with('courseMap')
            ->find($this->class_id);

        if (! $class?->institution_id || ! $class->courseMap?->course_id) {
            return false;
        }

        return TrainerSubjectAuthorization::query()
            ->where('trainer_id', $trainer->getKey())
            ->where('institution_id', $class->institution_id)
            ->where('course_id', $class->courseMap->course_id)
            ->where('subject_id', $this->subject_id)
            ->exists();
    }

    protected function professorClassesForSelectedInstitution(): Builder
    {
        $query = StudentClass::query()
            ->whereIn('classes.id', $this->professorClassIds());

        if ($this->institution_id) {
            $query->where('classes.institution_id', $this->institution_id);
        }

        return $query;
    }

    protected function professorClassIds(): array
    {
        $trainer = $this->currentTrainer();

        if (! $trainer) {
            return [];
        }

        $assignmentClassIds = TrainerClassAssignment::query()
            ->where('trainer_id', $trainer->getKey())
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->pluck('class_id');

        $authorizationClassIds = StudentClass::query()
            ->select('classes.id')
            ->join('course_maps', 'course_maps.id', '=', 'classes.course_map_id')
            ->whereExists(function ($query) use ($trainer): void {
                $query
                    ->selectRaw('1')
                    ->from('trainer_subject_authorizations')
                    ->where('trainer_subject_authorizations.trainer_id', $trainer->getKey())
                    ->whereColumn('trainer_subject_authorizations.institution_id', 'classes.institution_id')
                    ->whereColumn('trainer_subject_authorizations.course_id', 'course_maps.course_id');
            })
            ->pluck('classes.id');

        return $assignmentClassIds
            ->merge($authorizationClassIds)
            ->filter()
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function professorInstitutionIds(): Collection
    {
        $classIds = $this->professorClassIds();

        if ($classIds === []) {
            return collect();
        }

        return StudentClass::query()
            ->whereIn('id', $classIds)
            ->whereNotNull('institution_id')
            ->distinct()
            ->pluck('institution_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    protected function professorSubjectIdsForClass(int $classId): Collection
    {
        $trainer = $this->currentTrainer();

        if (! $trainer) {
            return collect();
        }

        $assignmentSubjectIds = TrainerClassAssignment::query()
            ->where('trainer_id', $trainer->getKey())
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->whereNotNull('subject_id')
            ->pluck('subject_id');

        $class = StudentClass::query()
            ->with('courseMap')
            ->find($classId);

        if (! $class?->institution_id || ! $class->courseMap?->course_id) {
            return $assignmentSubjectIds
                ->filter()
                ->unique()
                ->values();
        }

        $authorizationSubjectIds = TrainerSubjectAuthorization::query()
            ->where('trainer_id', $trainer->getKey())
            ->where('institution_id', $class->institution_id)
            ->where('course_id', $class->courseMap->course_id)
            ->whereNotNull('subject_id')
            ->pluck('subject_id');

        return $assignmentSubjectIds
            ->merge($authorizationSubjectIds)
            ->filter()
            ->unique()
            ->values();
    }

    protected function whereSelectedAcademicYear(Builder $query): void
    {
        $query
            ->where('classes.academic_year_id', $this->academic_year_id)
            ->orWhereHas('courseMap', fn (Builder $courseMapQuery): Builder => $courseMapQuery->where('academic_year_id', $this->academic_year_id));
    }

    protected function defaultInstitutionId(): ?int
    {
        return $this->professorInstitutionIds()->first();
    }

    protected function currentTrainer(): ?Trainer
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->trainer
            ?: Trainer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])
                ->first();
    }

    protected function currentUserIsProfessorAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('professores_admin');
    }
}
