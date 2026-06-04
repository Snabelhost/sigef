<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\Trainer;
use App\Models\Document;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

/**
 * Verificar que os modelos críticos usam SoftDeletes
 */
class SoftDeletesTest extends TestCase
{
    /** @test */
    public function student_model_uses_soft_deletes(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(Student::class),
            'Student model deve usar SoftDeletes'
        );
    }

    /** @test */
    public function candidate_model_uses_soft_deletes(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(Candidate::class),
            'Candidate model deve usar SoftDeletes'
        );
    }

    /** @test */
    public function evaluation_model_uses_soft_deletes(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(Evaluation::class),
            'Evaluation model deve usar SoftDeletes'
        );
    }

    /** @test */
    public function trainer_model_uses_soft_deletes(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(Trainer::class),
            'Trainer model deve usar SoftDeletes'
        );
    }

    /** @test */
    public function document_model_uses_soft_deletes(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(Document::class),
            'Document model deve usar SoftDeletes'
        );
    }
}
