<?php

namespace App\Filament\Escola\Resources\PautaResource\Pages;

use App\Filament\Escola\Resources\PautaResource;
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
use Illuminate\Database\Eloquent\Builder;

class MiniPauta extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = PautaResource::class;

    public ?int $course_id = null;
    public ?int $class_id = null;
    public ?int $subject_id = null;
    public bool $showTable = false;

    public function getView(): string
    {
        return 'filament.resources.pauta-resource.pages.mini-pauta';
    }

    public function mount(): void
    {
        $this->course_id = null;
        $this->class_id = null;
        $this->subject_id = null;
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

    public function getClasses()
    {
        if (!$this->course_id) {
            return collect();
        }

        $query = StudentClass::with('institution')
            ->whereHas('courseMap', fn($q) => $q->where('course_id', $this->course_id));

        // Filter by tenant institution in Escola panel
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant) {
            $query->where('institution_id', $tenant->id);
        }

        return $query->get();
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

    public function getSubjects()
    {
        if (!$this->class_id) {
            return Subject::orderBy('name')->get();
        }

        // Buscar disciplinas que alunos da turma estão inscritos
        $studentIds = \App\Models\StudentClassEnrollment::where('class_id', $this->class_id)
            ->where('is_active', true)
            ->pluck('student_id');

        $subjectIds = \App\Models\StudentSubjectEnrollment::whereIn('student_id', $studentIds)
            ->distinct()
            ->pluck('subject_id');

        if ($subjectIds->isEmpty()) {
            return Subject::orderBy('name')->get();
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

    public function updatedCourseId(): void
    {
        $this->class_id = null;
        $this->subject_id = null;
        $this->showTable = false;
    }

    public function updatedClassId(): void
    {
        // Se mudar a turma, esconde a tabela até pesquisar novamente
        $this->showTable = false;
    }

    public function updatedSubjectId(): void
    {
        // Se já tiver turma selecionada e selecionar nova disciplina, mostra automaticamente
        if ($this->class_id && $this->subject_id) {
            $this->showTable = true;
            $this->resetTable();
        }
    }

    public function pesquisar(): void
    {
        if ($this->class_id && $this->subject_id) {
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
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_1')
                    ->label('NPP1')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn($state) => is_numeric($state) && floatval($state) < 10 && $state !== null && $state !== '' ? ['style' => 'color: #dc2626; font-weight: 600'] : [])
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'npp', 1))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'npp', 1, $state))
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_2')
                    ->label('NPP2')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn($state) => is_numeric($state) && floatval($state) < 10 && $state !== null && $state !== '' ? ['style' => 'color: #dc2626; font-weight: 600'] : [])
                    ->getStateUsing(fn(Student $record) => $this->getEvaluationScore($record, 'npp', 2))
                    ->updateStateUsing(fn(Student $record, $state) => $this->saveEvaluation($record, 'npp', 2, $state))
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('npp_3')
                    ->label('NPP3')
                    ->width('20px')
                    ->rules(['numeric', 'min:0', 'max:20'])
                    ->extraInputAttributes(fn($state) => is_numeric($state) && floatval($state) < 10 && $state !== null && $state !== '' ? ['style' => 'color: #dc2626; font-weight: 600'] : [])
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
                    ->extraInputAttributes(fn($state) => is_numeric($state) && floatval($state) < 10 && $state !== null && $state !== '' ? ['style' => 'color: #dc2626; font-weight: 600'] : [])
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
}
