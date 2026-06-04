<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Testes para a lógica de validação de notas 0-20
 * Reflecte a validação implementada em MiniPauta::saveEvaluation()
 */
class GradeValidationTest extends TestCase
{
    /**
     * Simular a lógica de validação do saveEvaluation
     */
    private function validateGrade(mixed $value): ?float
    {
        // Rejeitar valores não numéricos
        if (!is_numeric($value)) {
            return null;
        }

        $numericValue = floatval($value);

        // Clampar entre 0 e 20
        return max(0, min(20, $numericValue));
    }

    /** @test */
    public function rejects_non_numeric_text(): void
    {
        $this->assertNull($this->validateGrade('abc'));
        $this->assertNull($this->validateGrade('nota'));
        $this->assertNull($this->validateGrade(''));
    }

    /** @test */
    public function accepts_integer_values_in_range(): void
    {
        $this->assertEquals(0.0, $this->validateGrade(0));
        $this->assertEquals(10.0, $this->validateGrade(10));
        $this->assertEquals(20.0, $this->validateGrade(20));
    }

    /** @test */
    public function accepts_string_numeric_values(): void
    {
        $this->assertEquals(15.0, $this->validateGrade('15'));
        $this->assertEquals(12.5, $this->validateGrade('12.5'));
    }

    /** @test */
    public function clamps_values_above_20_to_20(): void
    {
        $this->assertEquals(20.0, $this->validateGrade(21));
        $this->assertEquals(20.0, $this->validateGrade(100));
        $this->assertEquals(20.0, $this->validateGrade(999));
    }

    /** @test */
    public function clamps_negative_values_to_0(): void
    {
        $this->assertEquals(0.0, $this->validateGrade(-1));
        $this->assertEquals(0.0, $this->validateGrade(-50));
    }

    /** @test */
    public function accepts_decimal_values(): void
    {
        $this->assertEquals(14.5, $this->validateGrade(14.5));
        $this->assertEquals(9.9, $this->validateGrade(9.9));
        $this->assertEquals(0.1, $this->validateGrade(0.1));
    }

    /** @test */
    public function boundary_values(): void
    {
        $this->assertEquals(0.0, $this->validateGrade(0));
        $this->assertEquals(20.0, $this->validateGrade(20));
        $this->assertEquals(19.9, $this->validateGrade(19.9));
        $this->assertEquals(0.1, $this->validateGrade(0.1));
    }
}
