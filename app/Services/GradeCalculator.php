<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;

class GradeCalculator
{
    /**
     * Calcular média final de uma disciplina para um aluno
     * Fórmula: (Média NPP × 40%) + (Exame × 60%)
     */
    public static function subjectAverage(Student $student, int $subjectId): string
    {
        $evaluations = $student->evaluations->where('subject_id', $subjectId);

        if ($evaluations->isEmpty()) {
            return '-';
        }

        // Média NPP
        $nppScores = $evaluations->where('evaluation_type', 'npp')
            ->pluck('score')
            ->filter(fn($s) => $s !== null && $s !== '');
        $nppAvg = $nppScores->isNotEmpty() ? $nppScores->avg() : 0;

        // Nota do Exame
        $exameScore = $evaluations->where('evaluation_type', 'exame')->first()?->score;

        // Só calcular quando AMBOS (NPP e Exame) existem
        if ($nppScores->isEmpty() || !$exameScore) {
            return '-';
        }

        $mediaFinal = ($nppAvg * 0.4) + (floatval($exameScore) * 0.6);
        return number_format($mediaFinal, 1);
    }

    /**
     * Calcular média geral do aluno (média de todas as disciplinas)
     * Só retorna valor quando TODAS as disciplinas inscritas têm notas completas
     */
    public static function generalAverage(Student $student): string
    {
        $enrolledSubjectIds = \App\Models\StudentSubjectEnrollment::where('student_id', $student->id)
            ->distinct()
            ->pluck('subject_id');

        if ($enrolledSubjectIds->isEmpty()) {
            return '-';
        }

        $averages = [];
        foreach ($enrolledSubjectIds as $subjectId) {
            $avg = self::subjectAverage($student, $subjectId);
            if ($avg === '-') {
                return '-'; // Faltam notas — sem média geral
            }
            $averages[] = floatval($avg);
        }

        if (empty($averages)) {
            return '-';
        }

        return number_format(array_sum($averages) / count($averages), 1);
    }

    /**
     * Determinar resultado do aluno: Aprovado, Reprovado ou Pendente
     * Critérios:
     *  - Aprovado: todas as disciplinas com notas completas E média geral >= 10
     *  - Reprovado: faltam notas em disciplinas OU média geral < 10
     *  - Pendente: sem avaliações
     */
    public static function result(Student $student): string
    {
        // Obter disciplinas inscritas do aluno
        $enrolledSubjectIds = \App\Models\StudentSubjectEnrollment::where('student_id', $student->id)
            ->distinct()
            ->pluck('subject_id');

        if ($enrolledSubjectIds->isEmpty()) {
            return 'Pendente';
        }

        $hasAnyGrade = false;
        $allComplete = true;

        foreach ($enrolledSubjectIds as $subjectId) {
            $avg = self::subjectAverage($student, $subjectId);
            if ($avg !== '-') {
                $hasAnyGrade = true;
            } else {
                $allComplete = false;
            }
        }

        // Sem nenhuma nota → Pendente
        if (!$hasAnyGrade) {
            return 'Pendente';
        }

        // Faltam notas em alguma disciplina → Reprovado
        if (!$allComplete) {
            return 'Reprovado';
        }

        // Todas as notas preenchidas → verificar média geral
        $generalAvg = self::generalAverage($student);
        return floatval($generalAvg) >= 10 ? 'Aprovado' : 'Reprovado';
    }

    /**
     * Calcular média das avaliações de um tipo específico (npp, exame, etc.)
     */
    public static function typeAverage(Student $student, int $subjectId, string $type): string
    {
        $scores = $student->evaluations
            ->where('subject_id', $subjectId)
            ->where('evaluation_type', $type)
            ->pluck('score')
            ->filter(fn($s) => $s !== null && $s !== '');

        if ($scores->isEmpty()) {
            return '-';
        }

        return number_format($scores->avg(), 1);
    }
}
