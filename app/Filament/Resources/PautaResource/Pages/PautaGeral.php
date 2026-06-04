<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use App\Services\GradeCalculator;
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
use Illuminate\Database\Eloquent\Builder;

class PautaGeral extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = PautaResource::class;

    public ?int $course_id = null;
    public ?int $class_id = null;
    public bool $showTable = false;

    public function getView(): string
    {
        return 'filament.resources.pauta-resource.pages.pauta-geral';
    }

    public function mount(): void
    {
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

    public function getClasses()
    {
        if (!$this->course_id) {
            return collect();
        }
        return StudentClass::with('institution')
            ->whereHas('courseMap', fn($q) => $q->where('course_id', $this->course_id))
            ->get();
    }

    public function getSelectedClass(): ?StudentClass
    {
        if (!$this->class_id) {
            return null;
        }
        return StudentClass::with(['institution', 'academicYear', 'courseMap.course'])->find($this->class_id);
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
        if ($this->class_id) {
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
                ->url(PautaResource::getUrl())
                ->color('gray'),
        ];
    }

    /**
     * Gera abreviação do nome da disciplina
     */
    protected function getSubjectAbbreviation(string $name): string
    {
        // Se o nome tem menos de 5 caracteres, retorna inteiro
        if (strlen($name) <= 5) {
            return $name;
        }

        // Tenta criar abreviação com as primeiras letras de cada palavra
        $words = explode(' ', $name);
        if (count($words) > 1) {
            $abbr = '';
            foreach ($words as $word) {
                // Ignora palavras pequenas como "de", "da", "e", "do"
                if (strlen($word) > 2) {
                    $abbr .= strtoupper(substr($word, 0, 1));
                }
            }
            if (strlen($abbr) >= 2) {
                return $abbr;
            }
        }

        // Fallback: primeiros 5 caracteres
        return substr($name, 0, 5);
    }

    public function table(Table $table): Table
    {
        $classId = (int) $this->class_id;

        // Obter IDs dos alunos inscritos na turma
        $studentIds = [];
        if ($classId && $this->showTable) {
            $studentIds = \App\Models\StudentClassEnrollment::where('class_id', $classId)
                ->where('is_active', true)
                ->pluck('student_id')
                ->toArray();
        }

        // Obter disciplinas filtradas pela turma (apenas as inscritas)
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

        // Construir colunas dinamicamente
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

        // Adicionar coluna de média para cada disciplina
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

        // Adicionar colunas finais
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
                    ->with(['candidate', 'evaluations'])
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
}
