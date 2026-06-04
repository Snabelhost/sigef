<?php

namespace App\Http\Controllers;

use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\StudentClassEnrollment;
use App\Models\TrainerSubjectAuthorization;
use Illuminate\Http\Request;

class PautaController extends Controller
{
    public function miniPautaPrint(Request $request)
    {
        $turma = StudentClass::with(['academicYear', 'courseMap.course', 'institution'])->findOrFail($request->turma);
        $disciplina = Subject::findOrFail($request->disciplina);
        $instituicao = $turma->institution ?? Institution::find($turma->institution_id);

        // Buscar alunos inscritos na turma
        $studentIds = StudentClassEnrollment::where('class_id', $turma->id)
            ->where('is_active', true)
            ->pluck('student_id');

        $institutionId = $turma->institution_id;
        $students = Student::whereIn('id', $studentIds)
            ->with(['candidate', 'evaluations' => fn($q) => $q->where('subject_id', $disciplina->id)->where('institution_id', $institutionId)])
            ->orderBy('id')
            ->get();

        // Buscar formador da disciplina
        $trainerAuth = TrainerSubjectAuthorization::with('trainer')
            ->where('subject_id', $disciplina->id)
            ->first();
        $formador = $trainerAuth?->trainer?->full_name ?? '-';

        // Calcular dados dos alunos
        $alunos = $students->map(function ($s) use ($disciplina) {
            $npp1 = $this->getScore($s, 'npp', 1);
            $npp2 = $this->getScore($s, 'npp', 2);
            $npp3 = $this->getScore($s, 'npp', 3);
            $exame = $this->getScore($s, 'exame', 1);
            $mediaNpp = $this->calcMediaNpp($s);
            $mediaFinal = $this->calcMediaFinal($s);

            return [
                'id' => $s->id,
                'nuri' => $s->nuri ?? '-',
                'nome' => strtoupper($s->candidate?->full_name ?? '-'),
                'genero' => $s->candidate?->gender ?? '-',
                'npp1' => $npp1,
                'npp2' => $npp2,
                'npp3' => $npp3,
                'media_npp' => $mediaNpp,
                'exame' => $exame,
                'media_final' => $mediaFinal,
                'resultado' => $mediaFinal !== '-' && floatval($mediaFinal) >= 10 ? 'Apto' : ($mediaFinal === '-' ? '-' : 'N/Apto'),
                'faltas' => $s->attendances()
                    ->where('status', 'F')
                    ->count(),
            ];
        });

        // Estatísticas
        $totalAlunos = $alunos->count();
        $totalMasculino = $alunos->where('genero', 'M')->count();
        $totalFeminino = $alunos->where('genero', 'F')->count();
        $avaliados = $alunos->where('media_final', '!=', '-')->count();
        $aptos = $alunos->where('resultado', 'Apto')->count();
        $naoAptos = $alunos->where('resultado', 'N/Apto')->count();

        $stats = [
            'total' => $totalAlunos,
            'masculino' => $totalMasculino,
            'feminino' => $totalFeminino,
            'avaliados' => $avaliados,
            'aptos' => $aptos,
            'nao_aptos' => $naoAptos,
            'desistentes' => 0,
        ];

        return view('exports.mini-pauta', [
            'turma' => $turma,
            'disciplina' => $disciplina,
            'alunos' => $alunos,
            'instituicao' => $instituicao,
            'formador' => $formador,
            'stats' => $stats,
            'anoLectivo' => $turma->academicYear?->year ?? date('Y'),
            'curso' => $turma->courseMap?->course?->name ?? '-',
        ]);
    }

    public function pautaGeralPrint(Request $request)
    {
        $turma = StudentClass::with(['academicYear', 'courseMap.course', 'institution'])->findOrFail($request->turma);
        $instituicao = $turma->institution ?? Institution::find($turma->institution_id);

        $studentIds = StudentClassEnrollment::where('class_id', $turma->id)
            ->where('is_active', true)
            ->pluck('student_id');

        // Buscar disciplinas da turma
        $subjectIds = \App\Models\StudentSubjectEnrollment::whereIn('student_id', $studentIds)
            ->distinct()
            ->pluck('subject_id');

        $disciplinas = $subjectIds->isNotEmpty()
            ? Subject::whereIn('id', $subjectIds)->orderBy('name')->get()
            : Subject::orderBy('name')->get();

        $institutionId = $turma->institution_id;
        $students = Student::whereIn('id', $studentIds)
            ->with(['candidate', 'evaluations' => fn($q) => $q->where('institution_id', $institutionId)])
            ->orderBy('id')
            ->get();

        $alunos = $students->map(function ($s) use ($disciplinas) {
            $medias = [];
            foreach ($disciplinas as $sub) {
                $medias[$sub->id] = $this->getSubjectFinalAverage($s, $sub->id);
            }

            $validAverages = collect($medias)->filter(fn($v) => $v !== '-')->map(fn($v) => floatval($v));
            $mediaGeral = $validAverages->isNotEmpty()
                ? number_format($validAverages->avg(), 1)
                : '-';

            // Só pode ser Aprovado/Reprovado se TODAS as disciplinas têm nota
            $totalSubjects = count($medias);
            $completedSubjects = $validAverages->count();
            if ($completedSubjects < $totalSubjects) {
                $resultado = 'Pendente';
            } elseif (floatval($mediaGeral) >= 10) {
                $resultado = 'Aprovado';
            } else {
                $resultado = 'Reprovado';
            }

            return [
                'id' => $s->id,
                'nome' => strtoupper($s->candidate?->full_name ?? '-'),
                'medias' => $medias,
                'media_geral' => $mediaGeral,
                'resultado' => $resultado,
            ];
        });

        return view('exports.pauta-geral', [
            'turma' => $turma,
            'disciplinas' => $disciplinas,
            'alunos' => $alunos,
            'instituicao' => $instituicao,
            'anoLectivo' => $turma->academicYear?->year ?? date('Y'),
            'curso' => $turma->courseMap?->course?->name ?? '-',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────

    protected function getScore(Student $student, string $type, int $order): ?string
    {
        $eval = $student->evaluations
            ->where('evaluation_type', $type)
            ->where('observations', 'order_' . $order)
            ->first();

        return $eval?->score !== null ? number_format($eval->score, 0) : null;
    }

    protected function calcMediaNpp(Student $student): string
    {
        $scores = $student->evaluations
            ->where('evaluation_type', 'npp')
            ->pluck('score')
            ->filter(fn($s) => $s !== null && $s !== '');

        return $scores->isNotEmpty() ? number_format($scores->avg(), 1) : '-';
    }

    protected function calcMediaFinal(Student $student): string
    {
        $mediaNpp = $this->calcMediaNpp($student);
        $exame = $student->evaluations
            ->where('evaluation_type', 'exame')
            ->first()?->score;

        if ($mediaNpp === '-' || !$exame) {
            return '-';
        }

        $mediaFinal = (floatval($mediaNpp) * 0.4) + (floatval($exame) * 0.6);
        return number_format($mediaFinal, 1);
    }

    protected function getSubjectFinalAverage(Student $student, int $subjectId): string
    {
        $evaluations = $student->evaluations->where('subject_id', $subjectId);

        if ($evaluations->isEmpty()) return '-';

        $nppScores = $evaluations->where('evaluation_type', 'npp')->pluck('score')->filter(fn($s) => $s !== null && $s !== '');
        $nppAvg = $nppScores->isNotEmpty() ? $nppScores->avg() : 0;

        $exameScore = $evaluations->where('evaluation_type', 'exame')->first()?->score;

        if ($nppScores->isEmpty() || !$exameScore) return '-';

        $mediaFinal = ($nppAvg * 0.4) + (floatval($exameScore) * 0.6);
        return number_format($mediaFinal, 1);
    }
}
