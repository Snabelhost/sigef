<?php

namespace Tests\Unit;

use App\Models\Evaluation;
use App\Models\Student;
use App\Services\GradeCalculator;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class GradeCalculatorTest extends TestCase
{
    /**
     * Helper: cria uma instância mock de Student com evaluations carregadas
     */
    private function makeStudentWithEvaluations(array $evaluations): Student
    {
        $student = new Student();
        $student->id = 1;

        $evalCollection = new Collection();
        foreach ($evaluations as $eval) {
            $evalModel = new Evaluation();
            $evalModel->subject_id = $eval['subject_id'];
            $evalModel->evaluation_type = $eval['evaluation_type'];
            $evalModel->score = $eval['score'];
            $evalCollection->push($evalModel);
        }

        $student->setRelation('evaluations', $evalCollection);
        return $student;
    }

    /** @test */
    public function subject_average_returns_dash_when_no_evaluations(): void
    {
        $student = $this->makeStudentWithEvaluations([]);
        $this->assertEquals('-', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_returns_dash_when_only_npp_scores(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 14],
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 16],
        ]);

        // Sem exame, deve retornar '-'
        $this->assertEquals('-', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_returns_dash_when_only_exam_score(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 12],
        ]);

        // Sem NPP, deve retornar '-'
        $this->assertEquals('-', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_formula_npp_40_exam_60(): void
    {
        // NPP = (14+16)/2 = 15
        // Exame = 12
        // Média = 15*0.4 + 12*0.6 = 6 + 7.2 = 13.2
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 14],
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 16],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 12],
        ]);

        $this->assertEquals('13.2', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_with_single_npp_and_exam(): void
    {
        // NPP = 10, Exame = 10
        // Média = 10*0.4 + 10*0.6 = 4 + 6 = 10.0
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 10],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 10],
        ]);

        $this->assertEquals('10.0', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_perfect_scores(): void
    {
        // NPP = 20, Exame = 20
        // Média = 20*0.4 + 20*0.6 = 8 + 12 = 20.0
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 20],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 20],
        ]);

        $this->assertEquals('20.0', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_zero_exam_treated_as_no_score(): void
    {
        // NOTA: score=0 é falsy em PHP, então !$exameScore == true
        // O GradeCalculator trata score=0 como "sem exame" — edge case conhecido
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 0],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 0],
        ]);

        $this->assertEquals('-', GradeCalculator::subjectAverage($student, 1));
    }

    /** @test */
    public function subject_average_filters_by_subject_id(): void
    {
        $student = $this->makeStudentWithEvaluations([
            // Disciplina 1
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 15],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 18],
            // Disciplina 2
            ['subject_id' => 2, 'evaluation_type' => 'npp', 'score' => 8],
            ['subject_id' => 2, 'evaluation_type' => 'exame', 'score' => 6],
        ]);

        // Disciplina 1: 15*0.4 + 18*0.6 = 6 + 10.8 = 16.8
        $this->assertEquals('16.8', GradeCalculator::subjectAverage($student, 1));

        // Disciplina 2: 8*0.4 + 6*0.6 = 3.2 + 3.6 = 6.8
        $this->assertEquals('6.8', GradeCalculator::subjectAverage($student, 2));
    }

    /** @test */
    public function general_average_returns_dash_when_no_evaluations(): void
    {
        $student = $this->makeStudentWithEvaluations([]);
        $this->assertEquals('-', GradeCalculator::generalAverage($student));
    }

    /** @test */
    public function general_average_calculates_mean_of_subject_averages(): void
    {
        $student = $this->makeStudentWithEvaluations([
            // Disciplina 1: 15*0.4 + 18*0.6 = 16.8
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 15],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 18],
            // Disciplina 2: 8*0.4 + 6*0.6 = 6.8
            ['subject_id' => 2, 'evaluation_type' => 'npp', 'score' => 8],
            ['subject_id' => 2, 'evaluation_type' => 'exame', 'score' => 6],
        ]);

        // Média geral: (16.8 + 6.8) / 2 = 11.8
        $this->assertEquals('11.8', GradeCalculator::generalAverage($student));
    }

    /** @test */
    public function result_returns_pendente_when_no_grades(): void
    {
        $student = $this->makeStudentWithEvaluations([]);
        $this->assertEquals('Pendente', GradeCalculator::result($student));
    }

    /** @test */
    public function result_returns_aprovado_when_average_gte_10(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 10],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 10],
        ]);

        // Média = 10.0 >= 10 → Aprovado
        $this->assertEquals('Aprovado', GradeCalculator::result($student));
    }

    /** @test */
    public function result_returns_reprovado_when_average_lt_10(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 5],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 5],
        ]);

        // Média = 5*0.4 + 5*0.6 = 5.0 < 10 → Reprovado
        $this->assertEquals('Reprovado', GradeCalculator::result($student));
    }

    /** @test */
    public function type_average_returns_dash_when_no_scores(): void
    {
        $student = $this->makeStudentWithEvaluations([]);
        $this->assertEquals('-', GradeCalculator::typeAverage($student, 1, 'npp'));
    }

    /** @test */
    public function type_average_calculates_correctly_for_npp(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 14],
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 16],
            ['subject_id' => 1, 'evaluation_type' => 'exame', 'score' => 12],
        ]);

        // Média NPP = (14+16)/2 = 15.0
        $this->assertEquals('15.0', GradeCalculator::typeAverage($student, 1, 'npp'));
    }

    /** @test */
    public function type_average_ignores_null_scores(): void
    {
        $student = $this->makeStudentWithEvaluations([
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 14],
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => null],
            ['subject_id' => 1, 'evaluation_type' => 'npp', 'score' => 16],
        ]);

        // Null é filtrado, média = (14+16)/2 = 15.0
        $this->assertEquals('15.0', GradeCalculator::typeAverage($student, 1, 'npp'));
    }
}
