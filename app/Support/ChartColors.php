<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ChartColors
{
    private const FALLBACK_RGB = [156, 163, 175];

    public static function forLabel(string|int|null $label, float $alpha = 0.85): string
    {
        return self::rgba(self::rgbFor((string) $label), $alpha);
    }

    public static function forLabels(iterable $labels, float $alpha = 0.85): array
    {
        $colors = [];

        foreach ($labels as $label) {
            $colors[] = self::forLabel((string) $label, $alpha);
        }

        return $colors;
    }

    public static function distinctPalette(int $count, float $alpha = 0.85): array
    {
        $base = [
            [99, 102, 241],
            [20, 184, 166],
            [245, 158, 11],
            [239, 68, 68],
            [59, 130, 246],
            [16, 185, 129],
            [139, 92, 246],
            [236, 72, 153],
            [14, 165, 233],
            [100, 116, 139],
        ];

        $colors = [];

        for ($index = 0; $index < $count; $index++) {
            $colors[] = self::rgba($base[$index % count($base)], $alpha);
        }

        return $colors;
    }

    private static function rgbFor(string $label): array
    {
        $key = self::normalize($label);

        if ($key === '' || self::has($key, ['sem dados', 'sem dado'])) {
            return self::FALLBACK_RGB;
        }

        $provincePalette = self::provincePalette();

        if (isset($provincePalette[$key])) {
            return $provincePalette[$key];
        }

        $institutionPalette = self::institutionPalette();

        if (isset($institutionPalette[$key])) {
            return $institutionPalette[$key];
        }

        $coursePalette = self::coursePalette();

        if (isset($coursePalette[$key])) {
            return $coursePalette[$key];
        }

        $academicYearRgb = self::academicYearRgb($key);

        if ($academicYearRgb !== null) {
            return $academicYearRgb;
        }

        foreach (self::semanticPalette() as $entry) {
            if (self::has($key, $entry['matches'])) {
                return $entry['rgb'];
            }
        }

        return self::rgbFromHash($key);
    }

    private static function semanticPalette(): array
    {
        return [
            ['matches' => ['reprovado desistencia', 'reprovados desistencia', 'desistencia'], 'rgb' => [239, 68, 68]],
            ['matches' => ['reprovado faltas', 'reprovados faltas'], 'rgb' => [249, 115, 22]],
            ['matches' => ['reprovado notas', 'reprovados notas'], 'rgb' => [234, 179, 8]],
            ['matches' => ['baixa curso'], 'rgb' => [100, 116, 139]],
            ['matches' => ['formando superior'], 'rgb' => [139, 92, 246]],
            ['matches' => ['formando concluido', 'formandos concluidos', 'concluido', 'concluidos'], 'rgb' => [20, 184, 166]],
            ['matches' => ['em formacao', 'em forma'], 'rgb' => [99, 102, 241]],
            ['matches' => ['alistado', 'alistados'], 'rgb' => [245, 158, 11]],
            ['matches' => ['recruta', 'recrutas'], 'rgb' => [249, 115, 22]],
            ['matches' => ['instruendo', 'instruendos', 'cadete', 'cadetes'], 'rgb' => [16, 185, 129]],
            ['matches' => ['formando', 'formandos', 'aluno', 'alunos'], 'rgb' => [59, 130, 246]],
            ['matches' => ['formador', 'formadores'], 'rgb' => [14, 165, 233]],
            ['matches' => ['aprovado', 'aprovados', 'apto', 'aptos'], 'rgb' => [16, 185, 129]],
            ['matches' => ['pendente', 'pendentes'], 'rgb' => [245, 158, 11]],
            ['matches' => ['reprovado', 'reprovados', 'nao apto', 'nao aptos'], 'rgb' => [239, 68, 68]],
            ['matches' => ['activo', 'activos', 'ativo', 'ativos'], 'rgb' => [16, 185, 129]],
            ['matches' => ['inactivo', 'inactivos', 'inativo', 'inativos'], 'rgb' => [239, 68, 68]],
            ['matches' => ['sistema'], 'rgb' => [37, 99, 235]],
            ['matches' => ['api siga', 'siga'], 'rgb' => [20, 184, 166]],
        ];
    }

    private static function provincePalette(): array
    {
        return [
            'sem provincia' => [107, 114, 128],
            'bengo' => [194, 65, 12],
            'benguela' => [79, 70, 229],
            'bie' => [5, 150, 105],
            'cabinda' => [2, 132, 199],
            'cuando' => [190, 24, 93],
            'cuando cubango' => [217, 119, 6],
            'cuanza norte' => [124, 58, 237],
            'cuanza sul' => [20, 184, 166],
            'cubango' => [101, 163, 13],
            'cunene' => [220, 38, 38],
            'huambo' => [34, 197, 94],
            'huila' => [16, 185, 129],
            'icolo e bengo' => [168, 85, 247],
            'luanda' => [219, 39, 119],
            'lunda norte' => [14, 165, 233],
            'lunda sul' => [59, 130, 246],
            'malanje' => [234, 179, 8],
            'moxico' => [132, 204, 22],
            'moxico leste' => [22, 163, 74],
            'namibe' => [249, 115, 22],
            'uige' => [6, 182, 212],
            'zaire' => [100, 116, 139],
        ];
    }

    private static function institutionPalette(): array
    {
        return [
            'sem instituicao atribuida' => [107, 114, 128],
            'colegio de policia comandante jose alfredo' => [79, 70, 229],
            'academia de policia' => [37, 99, 235],
            'centro de formacao martires do mongua' => [16, 185, 129],
            'centro de formacao e adestramento de cavalaria e cinotecnia' => [249, 115, 22],
            'centro de formacao e aperfeicoamento de chefe e comandantess e' => [20, 184, 166],
            'centro de formacao e aperfeicoamento de chefe e comandantes' => [20, 184, 166],
            'centro de formacao regional norte' => [45, 212, 191],
            'centro de instrucao policial da regiao leste' => [139, 92, 246],
            'centro regional centro' => [234, 179, 8],
            'escola nacional da policia de proteccao e intervencao' => [236, 72, 153],
            'escola nacional de policia de proteccao e intervencao' => [236, 72, 153],
            'escola pratica de policia' => [245, 158, 11],
        ];
    }

    private static function coursePalette(): array
    {
        return [
            'sem curso' => [107, 114, 128],
            'crave maga' => [124, 58, 237],
            'curso basico policia' => [37, 99, 235],
            'curso basico de policia' => [37, 99, 235],
            'curso de transito' => [245, 158, 11],
            'curso transito' => [245, 158, 11],
        ];
    }

    private static function academicYearRgb(string $key): ?array
    {
        if ($key === 'sem ano lectivo' || $key === 'sem ano letivo') {
            return [107, 114, 128];
        }

        $palette = [
            [37, 99, 235],
            [16, 185, 129],
            [245, 158, 11],
            [139, 92, 246],
            [236, 72, 153],
            [14, 165, 233],
            [239, 68, 68],
            [20, 184, 166],
            [124, 58, 237],
            [194, 65, 12],
        ];

        if (! preg_match('/^(20\d{2})\s+(20\d{2})$/', $key, $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== $startYear + 1) {
            return null;
        }

        return $palette[$startYear % count($palette)];
    }

    private static function has(string $key, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $label): string
    {
        $normalized = Str::of($label)
            ->replace(['_', '/', '\\', '-', '.'], ' ')
            ->ascii()
            ->lower()
            ->squish()
            ->toString();

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $normalized));
    }

    private static function rgbFromHash(string $key): array
    {
        $hash = abs(crc32($key));
        $hue = ($hash % 360) / 360;
        $saturation = 0.58 + (($hash >> 8) % 18) / 100;
        $lightness = 0.42 + (($hash >> 16) % 12) / 100;

        return self::hslToRgb($hue, $saturation, $lightness);
    }

    private static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        if ($saturation === 0.0) {
            $value = (int) round($lightness * 255);

            return [$value, $value, $value];
        }

        $q = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - ($lightness * $saturation);
        $p = (2 * $lightness) - $q;

        return [
            (int) round(self::hueToRgb($p, $q, $hue + (1 / 3)) * 255),
            (int) round(self::hueToRgb($p, $q, $hue) * 255),
            (int) round(self::hueToRgb($p, $q, $hue - (1 / 3)) * 255),
        ];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }

        if ($t > 1) {
            $t -= 1;
        }

        if ($t < 1 / 6) {
            return $p + (($q - $p) * 6 * $t);
        }

        if ($t < 1 / 2) {
            return $q;
        }

        if ($t < 2 / 3) {
            return $p + (($q - $p) * ((2 / 3) - $t) * 6);
        }

        return $p;
    }

    private static function rgba(array $rgb, float $alpha): string
    {
        [$red, $green, $blue] = $rgb;
        $alpha = max(0, min(1, $alpha));
        $formattedAlpha = rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.');

        return "rgba({$red}, {$green}, {$blue}, {$formattedAlpha})";
    }
}
