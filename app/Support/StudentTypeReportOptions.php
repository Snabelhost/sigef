<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Student;
use App\Models\StudentType;
use Illuminate\Support\Str;

class StudentTypeReportOptions
{
    public static function make(): array
    {
        $names = collect([
            'Formando',
            'Em Formação',
            'Formando Concluído',
            'Recruta',
            'Instruendo',
        ])
            ->merge(
                StudentType::query()
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->orderBy('name')
                    ->pluck('name')
            )
            ->merge(
                Student::query()
                    ->whereNotNull('student_type')
                    ->where('student_type', '!=', '')
                    ->distinct()
                    ->orderBy('student_type')
                    ->pluck('student_type')
            )
            ->merge(
                Candidate::query()
                    ->whereNotNull('student_type')
                    ->where('student_type', '!=', '')
                    ->distinct()
                    ->orderBy('student_type')
                    ->pluck('student_type')
            );

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && ! self::shouldHide($name))
            ->unique(fn ($name) => self::canonicalKey($name))
            ->map(fn ($name) => [
                'filter' => self::canonicalFilter($name),
                'key' => self::canonicalKey($name),
                'label' => self::label($name),
                'description' => self::description($name),
            ])
            ->values()
            ->all();
    }

    public static function canonicalFilter(string $name): string
    {
        $normalized = self::normalize($name);

        return match (true) {
            str_contains($normalized, 'recruta') => 'Recruta',
            str_contains($normalized, 'instruendo') => 'Instruendo',
            str_contains($normalized, 'em formacao') => 'Em Formação',
            str_contains($normalized, 'conclu') => 'Formando Concluído',
            default => trim($name),
        };
    }

    public static function canonicalKey(string $name): string
    {
        return Str::slug(self::canonicalFilter($name)) ?: md5($name);
    }

    public static function label(string $name): string
    {
        return match (self::canonicalFilter($name)) {
            'Formando' => 'Formandos',
            'Recruta' => 'Recrutas',
            'Instruendo' => 'Instruendos',
            'Formando Concluído' => 'Formandos Concluídos',
            default => self::canonicalFilter($name),
        };
    }

    public static function description(string $name): string
    {
        return match (self::canonicalFilter($name)) {
            'Formando' => 'Lista de formandos por escola e turma',
            'Recruta' => 'Lista de recrutas por escola e turma',
            'Instruendo' => 'Lista de instruendos por escola e turma',
            'Em Formação' => 'Lista de alunos em formação por escola e turma',
            'Formando Concluído' => 'Lista de formandos concluídos por escola e turma',
            default => 'Lista de '.mb_strtolower(self::label($name)).' por escola e turma',
        };
    }

    private static function shouldHide(string $name): bool
    {
        $normalized = self::normalize($name);

        return $normalized === 'alistado'
            || str_contains($normalized, 'cadete');
    }

    private static function normalize(string $name): string
    {
        return Str::of(Str::ascii($name))
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}
