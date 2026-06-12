<?php

namespace App\Filament\Escola\Resources\PautaResource\Pages;

use App\Filament\Escola\Resources\PautaResource;
use App\Filament\Resources\Concerns\ResolvesInstitutionLogo;
use App\Models\AcademicYear;
use App\Models\CourseMap;
use App\Models\Institution;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Evaluation;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\TrainerSubjectAuthorization;
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
use Illuminate\Support\Facades\Storage;

class MiniPauta extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use ResolvesInstitutionLogo;

    protected static string $resource = PautaResource::class;

    public ?int $institution_id = null;
    public ?int $academic_year_id = null;
    public ?int $course_id = null;
    public ?int $class_id = null;
    public ?int $subject_id = null;
    public ?int $selected_student_id = null;
    public bool $showTable = false;

    public function getView(): string
    {
        return 'filament.resources.pauta-resource.pages.mini-pauta';
    }

    public function mount(): void
    {
        $this->institution_id = Filament::getTenant()?->id;
        $this->academic_year_id = null;
        $this->course_id = null;
        $this->class_id = null;
        $this->subject_id = null;
        $this->selected_student_id = null;
        $this->showTable = false;
    }

    protected static ?string $title = '';

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Pautas',
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

        $query = StudentClass::with('institution')
            ->where('institution_id', $this->institution_id)
            ->whereIn(
                'id',
                \App\Models\StudentClassEnrollment::query()
                    ->select('class_id')
                    ->where('is_active', true)
                    ->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery->where('institution_id', $this->institution_id))
            )
            ->where(function (Builder $query): void {
                $query
                    ->where('academic_year_id', $this->academic_year_id)
                    ->orWhereHas('courseMap', fn (Builder $courseMapQuery): Builder => $courseMapQuery->where('academic_year_id', $this->academic_year_id));
            })
            ->whereHas('courseMap', fn($q) => $q->where('course_id', $this->course_id));

        return $query->orderBy('name')->get();
    }

    public function getSelectedClass(): ?StudentClass
    {
        if (!$this->class_id) {
            return null;
        }
        return StudentClass::with(['institution', 'academicYear', 'courseMap.course'])->find($this->class_id);
    }

    public function getSelectedSubject(): ?Subject
    {
        if (!$this->subject_id) {
            return null;
        }
        return Subject::find($this->subject_id);
    }

    public function getSelectedStudent(): ?Student
    {
        if (!$this->selected_student_id) {
            return null;
        }

        return Student::with('candidate')->find($this->selected_student_id);
    }

    public function getSelectedStudentName(): ?string
    {
        $student = $this->getSelectedStudent();

        return $student?->candidate?->full_name ?: $student?->full_name;
    }

    public function getSelectedStudentPhotoUrl(): ?string
    {
        return $this->studentPhotoUrl($this->getSelectedStudent());
    }

    public function selectStudentPhoto(int $studentId): void
    {
        $this->selected_student_id = $studentId;
    }

    protected function studentPhotoUrl(?Student $student): ?string
    {
        $photo = trim((string) ($student?->photo ?: $student?->candidate?->photo ?: ''));

        if ($photo === '') {
            return null;
        }

        $photo = str_replace('\\', '/', $photo);

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://') || str_starts_with($photo, 'data:')) {
            return $photo;
        }

        $path = ltrim($photo, '/');

        if (str_starts_with($path, 'storage/')) {
            $storagePath = substr($path, strlen('storage/'));

            if (file_exists(public_path($path)) || Storage::disk('public')->exists($storagePath)) {
                return asset('storage/' . $storagePath);
            }

            return null;
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    public function getSubjects()
    {
        if (!$this->class_id) {
            return collect();
        }

        // Buscar disciplinas que alunos da turma estão inscritos
        $studentIds = \App\Models\StudentClassEnrollment::where('class_id', $this->class_id)
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return collect();
        }

        $subjectIds = \App\Models\StudentSubjectEnrollment::whereIn('student_id', $studentIds)
            ->where('class_id', $this->class_id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('subject_id');

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
    }

    public function getTrainerName(): string
    {
        if (!$this->subject_id) {
            return '-';
        }

        // Buscar autorização do professor para esta disciplina e curso
        $query = TrainerSubjectAuthorization::with('trainer')
            ->where('subject_id', $this->subject_id);

        // Se tiver curso selecionado, filtrar também por curso
        if ($this->course_id) {
            $query->where('course_id', $this->course_id);
        }

        $authorization = $query->first();

        return $authorization?->trainer?->full_name ?? '-';
    }

    public function updatedInstitutionId(): void
    {
        $this->academic_year_id = null;
        $this->course_id = null;
        $this->class_id = null;
        $this->subject_id = null;
        $this->selected_student_id = null;
        $this->showTable = false;
    }

    public function updatedAcademicYearId(): void
    {
        $this->course_id = null;
        $this->class_id = null;
        $this->subject_id = null;
        $this->selected_student_id = null;
        $this->showTable = false;
    }

    public function updatedCourseId(): void
    {
        $this->class_id = null;
        $this->subject_id = null;
        $this->selected_student_id = null;
        $this->showTable = false;
    }

    public function updatedClassId(): void
    {
        $this->subject_id = null;
        $this->selected_student_id = null;
        // Se mudar a turma, esconde a tabela até pesquisar novamente
        $this->showTable = false;
    }

    public function updatedSubjectId(): void
    {
        $this->selected_student_id = null;
        $this->showTable = false;
        return;

        // Se já tiver turma selecionada e selecionar nova disciplina, mostra automaticamente
        if ($this->class_id && $this->subject_id) {
            $this->showTable = true;
            $this->resetTable();
        }
    }

    public function pesquisar(): void
    {
        if ($this->institution_id && $this->academic_year_id && $this->course_id && $this->class_id && $this->subject_id) {
            $this->showTable = true;
            $this->selected_student_id = $this->firstStudentIdForSelectedClass();
            $this->resetTable();
        }
    }

    protected function firstStudentIdForSelectedClass(): ?int
    {
        if (!$this->class_id) {
            return null;
        }

        return \App\Models\StudentClassEnrollment::query()
            ->join('students', 'students.id', '=', 'student_class_enrollments.student_id')
            ->leftJoin('candidates', 'candidates.id', '=', 'students.candidate_id')
            ->where('student_class_enrollments.class_id', $this->class_id)
            ->where('student_class_enrollments.is_active', true)
            ->orderBy('candidates.full_name')
            ->value('students.id');
    }

    protected function scoreInputAttributes(Student $record, mixed $state): array
    {
        $attributes = [
            'wire:focus' => "selectStudentPhoto({$record->id})",
            'wire:click' => "selectStudentPhoto({$record->id})",
        ];

        if (is_numeric($state) && floatval($state) < 10 && $state !== null && $state !== '') {
            $attributes['style'] = 'color: #dc2626; font-weight: 600';
        }

        return $attributes;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn() => $this->exportPdf())
                ->disabled(fn() => !$this->showTable),
            Action::make('pautaGeral')
                ->label('Pauta Geral')
                ->icon('heroicon-o-document-duplicate')
                ->url(PautaResource::getUrl('pauta-geral'))
                ->color('success'),
            Action::make('listagemPautas')
                ->label('Listagem de Pautas')
                ->icon('heroicon-o-list-bullet')
                ->url(PautaResource::getUrl('list'))
                ->color('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        $classId = (int) $this->class_id;
        $subjectId = (int) $this->subject_id;

        // Obter IDs dos alunos inscritos na turma
        $studentIds = [];
        if ($classId && $this->showTable) {
            $studentIds = \App\Models\StudentClassEnrollment::where('class_id', $classId)
                ->where('is_active', true)
                ->pluck('student_id')
                ->toArray();
        }

        return $table
            ->query(
                Student::query()
                    ->when($classId && $this->showTable && !empty($studentIds), function (Builder $query) use ($studentIds) {
                        return $query->whereIn('students.id', $studentIds);
                    })
                    ->when(!$this->showTable || empty($studentIds), fn(Builder $query) => $query->whereRaw('1 = 0'))
                    ->with(['candidate', 'evaluations' => function ($q) use ($subjectId, $classId) {
                        $q->where('subject_id', $subjectId);
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
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome do Aluno')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(fn (Student $record): array => [
                        'wire:click' => "selectStudentPhoto({$record->id})",
                        'style' => 'cursor: pointer; color: #041B4E; font-weight: 700;',
                        'title' => 'Ver foto do aluno',
                    ])
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_1')
                    ->label('NPP1')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn (Student $record, $state): array => $this->scoreInputAttributes($record, $state))
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'npp', 1))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'npp', 1, $state))
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_2')
                    ->label('NPP2')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn (Student $record, $state): array => $this->scoreInputAttributes($record, $state))
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'npp', 2))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'npp', 2, $state))
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_3')
                    ->label('NPP3')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn (Student $record, $state): array => $this->scoreInputAttributes($record, $state))
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'npp', 3))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'npp', 3, $state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('media_npp')
                    ->label('Média NPP')
                    ->getStateUsing(fn(Student $record) => $this->calculateAverage($record, 'npp'))
                    ->badge()
                    ->color(fn($state) => $state !== '-' && floatval($state) >= 10 ? 'success' : ($state === '-' ? 'gray' : 'danger'))
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('exame')
                    ->label('Exame')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn (Student $record, $state): array => $this->scoreInputAttributes($record, $state))
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'exame', 1))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'exame', 1, $state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('media_final')
                    ->label('Média Final')
                    ->getStateUsing(fn(Student $record) => $this->calculateFinalAverage($record))
                    ->badge()
                    ->color(fn($state) => $state !== '-' && floatval($state) >= 10 ? 'success' : ($state === '-' ? 'gray' : 'danger'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('resultado')
                    ->label('Resultado')
                    ->getStateUsing(fn(Student $record) => $this->getResultado($record))
                    ->badge()
                    ->color(fn($state) => $state === 'Aprovado' ? 'success' : ($state === 'Pendente' ? 'gray' : 'danger'))
                    ->toggleable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->striped()
            ->emptyStateHeading('Nenhum aluno encontrado')
            ->emptyStateDescription('Verifique se existem alunos matriculados nesta turma.');
    }

    protected function getEvaluationScore(Student $student, string $type, int $order): ?string
    {
        if (!$this->subject_id) return null;

        // Usar eager loading em vez de queries individuais
        $evaluation = $student->evaluations
            ->where('evaluation_type', $type)
            ->where('observations', 'order_' . $order)
            ->first();

        return $evaluation?->score;
    }

    protected function saveEvaluation(Student $student, string $type, int $order, $score): void
    {
        $this->selectStudentPhoto($student->id);

        if ($score === null || $score === '' || !$this->subject_id || !$this->class_id) {
            return;
        }

        // Validar nota 0-20
        if (!is_numeric($score)) {
            return;
        }
        $score = max(0, min(20, floatval($score)));

        $class = StudentClass::find($this->class_id);

        Evaluation::updateOrCreate(
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
            ]
        );
    }

    protected function calculateAverage(Student $student, string $type): string
    {
        if (!$this->subject_id) return '-';

        // Usar eager loading em vez de queries individuais
        $scores = $student->evaluations
            ->where('evaluation_type', $type)
            ->pluck('score')
            ->filter(fn($s) => $s !== null && $s !== '');

        if ($scores->isEmpty()) {
            return '-';
        }

        return number_format($scores->avg(), 1);
    }

    protected function calculateFinalAverage(Student $student): string
    {
        $mediaNpp = $this->calculateAverage($student, 'npp');
        $exame = $this->getEvaluationScore($student, 'exame', 1);

        if ($mediaNpp === '-' && !$exame) {
            return '-';
        }

        $mediaNppValue = $mediaNpp !== '-' ? floatval($mediaNpp) : 0;
        $exameValue = $exame ? floatval($exame) : 0;

        if ($mediaNppValue > 0 && $exameValue > 0) {
            // Média Final = (Média NPP * 40%) + (Exame * 60%)
            $mediaFinal = ($mediaNppValue * 0.4) + ($exameValue * 0.6);
            return number_format($mediaFinal, 1);
        }

        return '-';
    }

    protected function getResultado(Student $student): string
    {
        $media = $this->calculateFinalAverage($student);
        if ($media === '-') return 'Pendente';
        return floatval($media) >= 10 ? 'Aprovado' : 'Reprovado';
    }

    public function exportPdf()
    {
        if (!$this->class_id || !$this->subject_id) {
            return;
        }

        $url = route('pauta.mini-pauta.print', [
            'turma' => $this->class_id,
            'disciplina' => $this->subject_id,
        ]);

        return redirect($url);
    }

    public function printMiniPautaAction(): Action
    {
        return Action::make('printMiniPauta')
            ->label('Imprimir Pauta')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->disabled(fn (): bool => ! $this->class_id || ! $this->subject_id)
            ->modalHeading('MINI PAUTA PROFESSOR')
            ->modalDescription(null)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pré-visualização')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function () {
                $printUrl = route('pauta.mini-pauta.print', [
                    'turma' => $this->class_id,
                    'disciplina' => $this->subject_id,
                ]);

                $className = $this->getSelectedClass()?->name ?? '-';
                $subjectName = $this->getSelectedSubject()?->name ?? '-';
                $embeddedUrl = $printUrl . '&embedded=1&autoprint=0';
                $fallbackPrintUrl = $printUrl . '&autoprint=1';

                return view('trainers.sheet-modal', [
                    'viewerId' => 'sigef-mini-pauta-viewer-' . ($this->class_id ?: 'none') . '-' . ($this->subject_id ?: 'none'),
                    'frameId' => 'sigef-mini-pauta-frame-' . ($this->class_id ?: 'none') . '-' . ($this->subject_id ?: 'none'),
                    'documentName' => 'MINI PAUTA PROFESSOR',
                    'documentBadge' => 'TURMA: ' . e($className) . ' &nbsp;|&nbsp; DISCIPLINA: ' . e($subjectName),
                    'documentType' => 'mini pauta',
                    'defaultOrientation' => 'vertical',
                    'showOrientationSelector' => false,
                    'loadingText' => 'A preparar mini pauta...',
                    'hintText' => 'Pre-visualize a Mini Pauta do Professor em A4 antes de imprimir.',
                    'embeddedHorizontalUrl' => $embeddedUrl,
                    'embeddedVerticalUrl' => $embeddedUrl,
                    'fallbackPrintHorizontalUrl' => $fallbackPrintUrl,
                    'fallbackPrintVerticalUrl' => $fallbackPrintUrl,
                ]);
            });
    }
}
