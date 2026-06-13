<?php

namespace App\Filament\Resources\PautaGeralResource\Pages;

use App\Filament\Resources\PautaGeralResource;
use App\Filament\Resources\Concerns\ResolvesInstitutionLogo;
use App\Services\GradeCalculator;
use App\Models\AcademicYear;
use App\Models\CourseMap;
use App\Models\Institution;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Evaluation;
use App\Models\Course;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListPautaGeral extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use ResolvesInstitutionLogo;

    protected static string $resource = PautaGeralResource::class;

    public ?int $institution_id = null;
    public ?int $academic_year_id = null;
    public ?int $course_id = null;
    public ?int $class_id = null;
    public bool $showTable = false;

    public function getView(): string
    {
        return 'filament.resources.pauta-geral-resource.pages.list-pauta-geral';
    }

    public function mount(): void
    {
        $this->institution_id = Filament::getTenant()?->id;
        $this->academic_year_id = null;
        $this->course_id = null;
        $this->class_id = null;
        $this->showTable = false;
    }

    public function getTitle(): string
    {
        return 'Pauta Geral';
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Pauta Geral',
        ];
    }

    public function getInstitutions()
    {
        if ($tenant = Filament::getTenant()) {
            return collect([$tenant]);
        }

        return Institution::orderBy('name')->get();
    }

    public function getAcademicYears()
    {
        $query = AcademicYear::query();

        if ($this->institution_id) {
            $courseMapYearIds = CourseMap::query()
                ->where('institution_id', $this->institution_id)
                ->whereNotNull('academic_year_id')
                ->pluck('academic_year_id');

            $classYearIds = StudentClass::query()
                ->where('institution_id', $this->institution_id)
                ->whereNotNull('academic_year_id')
                ->pluck('academic_year_id');

            $academicYearIds = $courseMapYearIds
                ->merge($classYearIds)
                ->filter()
                ->unique()
                ->values();

            if ($academicYearIds->isNotEmpty()) {
                $query->whereIn('id', $academicYearIds);
            }
        }

        return $query->orderByDesc('year')->get();
    }

    public function getCourses()
    {
        if (!$this->institution_id || !$this->academic_year_id) {
            return collect();
        }

        $courseIds = CourseMap::query()
            ->where('institution_id', $this->institution_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        if ($courseIds->isNotEmpty()) {
            return Course::query()
                ->whereIn('id', $courseIds)
                ->orderBy('name')
                ->get();
        }

        return Course::query()
            ->where('institution_id', $this->institution_id)
            ->orderBy('name')
            ->get();
    }

    public function getClasses()
    {
        if (!$this->institution_id || !$this->academic_year_id || !$this->course_id) {
            return collect();
        }

        return StudentClass::with('institution')
            ->where('institution_id', $this->institution_id)
            ->where(function (Builder $query): void {
                $query
                    ->where('academic_year_id', $this->academic_year_id)
                    ->orWhereHas('courseMap', fn (Builder $courseMapQuery): Builder => $courseMapQuery->where('academic_year_id', $this->academic_year_id));
            })
            ->whereHas('courseMap', fn($q) => $q->where('course_id', $this->course_id))
            ->whereIn(
                'id',
                \App\Models\StudentClassEnrollment::query()
                    ->select('class_id')
                    ->where('is_active', true)
            )
            ->orderBy('name')
            ->get();
    }

    public function getSelectedClass(): ?StudentClass
    {
        if (!$this->class_id) {
            return null;
        }
        return StudentClass::with(['institution', 'academicYear', 'courseMap.course'])->find($this->class_id);
    }

    public function updatedInstitutionId(): void
    {
        $this->academic_year_id = null;
        $this->course_id = null;
        $this->class_id = null;
        $this->showTable = false;
    }

    public function updatedAcademicYearId(): void
    {
        $this->course_id = null;
        $this->class_id = null;
        $this->showTable = false;
    }

    public function updatedCourseId(): void
    {
        $this->class_id = null;
        $this->showTable = false;
    }

    public function updatedClassId(): void
    {
        if ($this->class_id) {
            $this->showTable = true;
            $this->resetTable();
        }
    }

    public function pesquisar(): void
    {
        if ($this->institution_id && $this->academic_year_id && $this->course_id && $this->class_id) {
            $this->showTable = true;
            $this->resetTable();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn() => $this->exportPdf())
                ->disabled(fn() => !$this->showTable),
            Action::make('miniPauta')
                ->label('Mini Pauta')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => Filament::getCurrentPanel()?->getId() === 'escola'
                    ? \App\Filament\Escola\Resources\PautaResource::getUrl()
                    : \App\Filament\Resources\PautaResource::getUrl())
                ->color('primary'),
        ];
    }

    /**
     * Gera abreviação do nome da disciplina
     */
    protected function getSubjectAbbreviation(string $name): string
    {
        if (strlen($name) <= 5) {
            return $name;
        }

        $words = explode(' ', $name);
        if (count($words) > 1) {
            $abbr = '';
            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    $abbr .= strtoupper(substr($word, 0, 1));
                }
            }
            if (strlen($abbr) >= 2) {
                return $abbr;
            }
        }

        return substr($name, 0, 5);
    }

    public function table(Table $table): Table
    {
        $classId = (int) $this->class_id;

        $studentIds = [];
        if ($classId && $this->showTable) {
            $studentIds = \App\Models\StudentClassEnrollment::where('class_id', $classId)
                ->where('is_active', true)
                ->pluck('student_id')
                ->toArray();
        }

        // Obter disciplinas filtradas pela turma
        $subjects = collect();
        if ($classId && $this->showTable && !empty($studentIds)) {
            $subjectIds = \App\Models\StudentSubjectEnrollment::whereIn('student_id', $studentIds)
                ->distinct()
                ->pluck('subject_id');
            $subjects = $subjectIds->isNotEmpty()
                ? Subject::whereIn('id', $subjectIds)->orderBy('name')->get()
                : Subject::orderBy('name')->get();
        } else {
            $subjects = Subject::orderBy('name')->get();
        }

        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->width('50px')
                ->toggleable(),
            Tables\Columns\TextColumn::make('candidate.full_name')
                ->label('Nome do Aluno')
                ->searchable()
                ->sortable()
                ->toggleable(),
        ];

        foreach ($subjects as $subject) {
            $abbr = $this->getSubjectAbbreviation($subject->name);
            $columns[] = Tables\Columns\TextColumn::make('media_' . $subject->id)
                ->label($abbr)
                ->tooltip($subject->name)
                ->getStateUsing(fn(Student $record) => $this->getSubjectFinalAverage($record, $subject->id))
                ->badge()
                ->color(fn($state) => $state !== '-' && floatval($state) >= 10 ? 'success' : ($state === '-' ? 'gray' : 'danger'))
                ->width('45px')
                ->toggleable();
        }

        $columns[] = Tables\Columns\TextColumn::make('media_geral')
            ->label('MG')
            ->tooltip('Média Geral')
            ->getStateUsing(fn(Student $record) => $this->calculateGeneralAverage($record))
            ->badge()
            ->color(fn($state) => $state !== '-' && floatval($state) >= 10 ? 'success' : ($state === '-' ? 'gray' : 'danger'))
            ->width('50px')
            ->toggleable();

        $columns[] = Tables\Columns\TextColumn::make('resultado_final')
            ->label('Resultado')
            ->getStateUsing(fn(Student $record) => $this->getResult($record))
            ->badge()
            ->color(fn($state) => $state === 'Aprovado' ? 'success' : ($state === 'Reprovado' ? 'danger' : 'gray'))
            ->toggleable();

        return $table
            ->query(
                Student::query()
                    ->when($classId && $this->showTable && !empty($studentIds), function (Builder $query) use ($studentIds) {
                        return $query->whereIn('students.id', $studentIds);
                    })
                    ->when(!$this->showTable || empty($studentIds), fn(Builder $query) => $query->whereRaw('1 = 0'))
                    ->with(['candidate', 'evaluations' => function ($q) use ($classId) {
                        if ($classId) {
                            $instId = StudentClass::where('id', $classId)->value('institution_id');
                            if ($instId) {
                                $q->where('institution_id', $instId);
                            }
                        }
                    }])
                    ->join('candidates', 'students.candidate_id', '=', 'candidates.id')
                    ->orderBy('candidates.full_name', 'asc')
                    ->select('students.*')
            )
            ->columns($columns)
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->striped();
    }

    protected function getSubjectFinalAverage(Student $student, int $subjectId): string
    {
        return GradeCalculator::subjectAverage($student, $subjectId);
    }

    protected function calculateGeneralAverage(Student $student): string
    {
        return GradeCalculator::generalAverage($student);
    }

    protected function getResult(Student $student): string
    {
        return GradeCalculator::result($student);
    }

    public function exportPdf()
    {
        if (!$this->class_id) {
            return null;
        }
        return redirect('/reports/pauta-geral?class=' . $this->class_id);
    }

    public function printPautaGeralAction(): Action
    {
        return Action::make('printPautaGeral')
            ->label('Imprimir Pauta Geral')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->disabled(fn (): bool => ! $this->class_id)
            ->modalHeading('PAUTA GERAL DE CLASSIFICAÇÃO')
            ->modalDescription(null)
            ->modalWidth(Width::Screen)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pre-visualizacao')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function () {
                $printUrl = route('pauta.pauta-geral.print', ['turma' => $this->class_id]);
                $className = $this->getSelectedClass()?->name ?? '-';
                $courseName = $this->getSelectedClass()?->courseMap?->course?->name ?? '-';
                $embeddedUrl = $printUrl . '&embedded=1&autoprint=0';
                $fallbackPrintUrl = $printUrl . '&autoprint=1';

                return view('trainers.sheet-modal', [
                    'viewerId' => 'sigef-pauta-geral-viewer-' . ($this->class_id ?: 'none'),
                    'frameId' => 'sigef-pauta-geral-frame-' . ($this->class_id ?: 'none'),
                    'documentName' => 'PAUTA GERAL DE CLASSIFICAÇÃO',
                    'documentBadge' => 'TURMA: ' . e($className) . ' &nbsp;|&nbsp; CURSO: ' . e($courseName),
                    'documentType' => 'pauta geral',
                    'defaultOrientation' => 'horizontal',
                    'showOrientationSelector' => false,
                    'loadingText' => 'A preparar pauta geral...',
                    'hintText' => 'Pre-visualize a Pauta Geral de Classificação em A3 antes de imprimir.',
                    'embeddedHorizontalUrl' => $embeddedUrl,
                    'embeddedVerticalUrl' => $embeddedUrl,
                    'fallbackPrintHorizontalUrl' => $fallbackPrintUrl,
                    'fallbackPrintVerticalUrl' => $fallbackPrintUrl,
                ]);
            });
    }
}
