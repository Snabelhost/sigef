<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Mini Pauta - {{ $disciplina->name ?? 'Disciplina' }} - {{ $turma->name ?? 'Turma' }}</title>
    @php
        $embeddedMode = $embeddedMode ?? false;
        $autoPrint = $autoPrint ?? false;
        $reportInstitution = $reportInstitution ?? [];
        $reportConfig = $reportInstitution['config'] ?? \App\Models\SystemSetting::getReportInstitutionConfig($instituicao ?? null);
        $reportInstitutionNameForHeader = $reportConfig['name'] ?? ($instituicao->name ?? 'SIGEF');
        $reportInstitutionAcronymForHeader = $reportConfig['acronym'] ?? ($instituicao->acronym ?? 'SIGEF');
        $normalizeReportLine = fn (mixed $value): string => \Illuminate\Support\Str::of((string) $value)
            ->ascii()
            ->lower()
            ->squish()
            ->toString();
        $hiddenReportHeaderLines = array_filter([
            $reportInstitutionNameForHeader,
            $reportInstitutionAcronymForHeader,
            $instituicao?->name ?? null,
            $instituicao?->acronym ?? null,
        ], fn ($line) => filled($line));
        $reportHeaderLines = $reportInstitution['headerLines'] ?? array_values(array_filter([
            $reportConfig['republic_line'] ?? null,
            $reportConfig['ministry_line'] ?? null,
            $reportConfig['organ_line'] ?? null,
            $reportConfig['department_line'] ?? null,
        ], fn ($line) => filled($line)));
        $reportHeaderLines = array_values(array_filter($reportHeaderLines, fn ($line) => ! in_array(
            $normalizeReportLine($line),
            array_map($normalizeReportLine, $hiddenReportHeaderLines),
            true
        )));
        $resolveReportAsset = function (mixed $path, string $fallback) use (&$resolveReportAsset): string {
            if (is_array($path)) {
                $path = reset($path) ?: null;
            }

            if (! is_scalar($path)) {
                return $fallback;
            }

            $path = trim((string) $path);

            if ($path === '') {
                return $fallback;
            }

            $decoded = json_decode($path, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $resolveReportAsset(reset($decoded) ?: null, $fallback);
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:') || str_starts_with($path, '/')) {
                return $path;
            }

            if (str_starts_with($path, 'storage/')) {
                return asset($path);
            }

            if (file_exists(public_path($path))) {
                return asset($path);
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        };
        $reportLogoUrl = filled($reportInstitution['logoUrl'] ?? null)
            ? $reportInstitution['logoUrl']
            : $resolveReportAsset($reportConfig['logo_path'] ?? null, asset('images/logo-pna.png'));
        $reportFooterText = $reportInstitution['footerText'] ?? ($reportConfig['footer_text'] ?? '');
        $reportLocation = $reportInstitution['location'] ?? ($reportConfig['municipality'] ?? ($reportConfig['province'] ?? 'Luanda'));
        $reportInstitutionName = $reportConfig['name'] ?? ($instituicao->name ?? 'SIGEF');
        $reportInstitutionAcronym = $reportConfig['acronym'] ?? ($instituicao->acronym ?? 'SIGEF');
        $reportDirectorTitle = $reportConfig['director_title'] ?? 'Responsável';
        $reportUpper = fn (mixed $value): string => function_exists('mb_strtoupper')
            ? mb_strtoupper((string) $value, 'UTF-8')
            : strtoupper((string) $value);
    @endphp
    <style>
        @page {
            size: A4 portrait;
            margin: 4mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            background: {{ $embeddedMode ? '#f8fafc' : '#fff' }};
            color: #000;
            padding: {{ $embeddedMode ? '4mm 0' : '0' }};
        }

        .container {
            width: 202mm;
            min-height: 289mm;
            margin: 0 auto;
            padding: 5mm 6mm 4mm;
            background: #fff;
            overflow: visible;
        }

        /* ── Logo e cabeçalho ── */
        .container-logo {
            text-align: center;
            margin-bottom: 1mm;
        }

        .container-logo img {
            width: 46px;
            height: auto;
        }

        .conteiner {
            text-align: center;
            font-size: 11px;
            line-height: 1.28;
            margin-bottom: 2mm;
        }

        .conteiner strong {
            font-size: 12px;
        }

        .conteiner p strong {
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* ── Info da disciplina/turma ── */
        .utility-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            margin-bottom: 1mm;
            padding: 1mm 0;
        }

        .utility-row .item {
            display: inline-flex;
            gap: 1mm;
        }

        .utility-row .item label {
            font-weight: bold;
        }

        .utility-row .item .val {
            color: crimson;
            font-weight: 600;
        }

        /* ── Tabela principal ── */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.6px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 0.95mm 1mm;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }

        th {
            background: #fff;
            font-weight: bold;
            font-size: 7.2px;
            color: #000;
        }

        th.utility-header {
            background: #f5f5f5;
            font-size: 7.6px;
        }

        td.nome {
            text-align: left;
            font-size: 8.2px;
            max-width: 70mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 1.2mm;
        }

        td.posto {
            font-size: 7.6px;
        }

        .col-num {
            width: 8mm;
        }

        .col-posto {
            width: 18mm;
        }

        .col-nome {
            width: 62mm;
        }

        .col-genero {
            width: 8mm;
        }

        .col-nota {
            width: 10mm;
        }

        .col-faltas {
            width: 14mm;
        }

        .col-media {
            width: 13mm;
        }

        .col-obs {
            width: 18mm;
        }

        .info-table {
            margin-bottom: 1.7mm;
        }

        .grades-table th {
            padding: 0.8mm 0.7mm;
        }

        .grades-table td {
            height: 5.7mm;
            padding: 0.75mm 0.8mm;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        td.aprovado {
            color: #006400;
            font-weight: bold;
        }

        td.reprovado {
            color: #c00;
            font-weight: bold;
        }

        /* ── Estatística ── */
        .stats-section {
            margin-top: 2.4mm;
            page-break-inside: avoid;
        }

        .stats-title {
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 1mm;
        }

        table.stats-table {
            font-size: 6.8px;
        }

        table.stats-table th {
            background: #f5f5f5;
            font-size: 6.8px;
            padding: 0.6mm;
        }

        table.stats-table td {
            padding: 0.75mm 0.6mm;
            font-weight: 600;
        }

        /* ── Footer ── */
        .footer-container {
            margin-top: 2.5mm;
            text-align: center;
            page-break-inside: avoid;
        }

        .footer-container .main-text {
            font-weight: bold;
            font-style: italic;
            font-size: 8.2px;
            margin-bottom: 1.6mm;
        }

        .details-section {
            font-size: 8px;
            line-height: 1.35;
        }

        .details-section .date-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 17mm;
        }

        .coordinator-text {
            margin-top: 4mm;
            font-weight: bold;
        }

        @media print {
            html,
            body {
                width: auto;
                min-height: auto;
                background: #fff;
                padding: 0;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .container {
                width: 100%;
                min-height: auto;
                padding: 0;
                overflow: visible;
            }

            tr,
            .stats-section,
            .footer-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="{{ $embeddedMode ? 'embedded-print-preview' : '' }}">
    <div class="container">
        {{-- Logo --}}
        <div class="container-logo">
            <img src="{{ $reportLogoUrl }}" alt="Logotipo">
        </div>

        {{-- Header institucional --}}
        <div class="conteiner">
            @foreach($reportHeaderLines as $line)
                {{ $reportUpper($line) }}<br>
            @endforeach
            <p><strong>MINI PAUTA DO FORMADOR</strong></p>
        </div>

        {{-- Info da disciplina/turma --}}
        <table class="info-table">
            <thead>
                <tr>
                    <th class="utility-header" colspan="2">
                        Disciplina: <span style="color: crimson;">{{ $disciplina->name ?? '-' }}</span>
                    </th>
                    <th class="utility-header" colspan="2" style="color: crimson;">
                        COM. INTER.: <span style="color: #000;">____</span>
                    </th>
                    <th class="utility-header" colspan="2">
                        Turno: ____
                    </th>
                    <th class="utility-header" colspan="3">
                        {{ $turma->name ?? '-' }}
                    </th>
                    <th class="utility-header" colspan="5">
                        ANO LECT. {{ $anoLectivo }}
                    </th>
                </tr>
                <tr>
                    <th colspan="2">TURMA: {{ $turma->name ?? '-' }}</th>
                    <th colspan="2" style="color: rgb(74, 179, 240);">{{ $curso ?? '-' }}</th>
                    <th colspan="10" style="color: rgb(74, 179, 240);">CLASSIFICAÇÃO</th>
                </tr>
            </thead>
        </table>

        {{-- Tabela de notas --}}
        <div class="table-container">
            <table class="grades-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-num">Nº</th>
                        <th rowspan="2" class="col-posto">Posto</th>
                        <th rowspan="2" class="col-nome">NOME DO ALUNO (A)</th>
                        <th rowspan="2" class="col-genero">Gênero</th>
                        <th colspan="3">IIº FASE</th>
                        <th rowspan="2" class="col-faltas">Total<br>de Faltas</th>
                        <th colspan="2">Avaliação<br>Final</th>
                        <th rowspan="2" class="col-obs">Observações</th>
                    </tr>
                    <tr>
                        <th class="col-nota">P1</th>
                        <th class="col-nota">P2</th>
                        <th class="col-nota">P3</th>
                        <th class="col-media">M. FINAL</th>
                        <th class="col-nota">FALTAS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alunos as $index => $aluno)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="posto">Formando</td>
                        <td class="nome">{{ $aluno['nome'] }}</td>
                        <td>{{ strtoupper(substr($aluno['genero'] ?? '-', 0, 1)) }}</td>
                        <td>{{ $aluno['npp1'] ?? '' }}</td>
                        <td>{{ $aluno['npp2'] ?? '' }}</td>
                        <td>{{ $aluno['npp3'] ?? '' }}</td>
                        <td>{{ $aluno['faltas'] > 0 ? $aluno['faltas'] : '' }}</td>
                        <td class="{{ $aluno['media_final'] !== '-' && floatval($aluno['media_final']) >= 10 ? 'aprovado' : 'reprovado' }}">
                            <strong>{{ $aluno['media_final'] }}</strong>
                        </td>
                        <td>{{ $aluno['faltas'] > 0 ? $aluno['faltas'] : '' }}</td>
                        <td></td>
                    </tr>
                    @empty
                    @for($i = 1; $i <= 50; $i++)
                        <tr>
                        <td>{{ $i }}</td>
                        <td class="posto">Formando</td>
                        <td class="nome"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        </tr>
                        @endfor
                        @endforelse
                </tbody>
            </table>
        </div>

        {{-- Estatística --}}
        <div class="stats-section">
            <div class="stats-title">ESTATÍSTICA</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th colspan="2" rowspan="3" style="background: #f5f5f5;">MATRICULADOS</th>
                        <th colspan="8" style="background: #f5f5f5;">IIº FASE</th>
                        <th colspan="2" rowspan="4" style="background: #f5f5f5; vertical-align: middle;">ASSINATURA DO<br>FORMADOR</th>
                    </tr>
                    <tr>
                        <th colspan="2">DESIST</th>
                        <th colspan="2">AVAL</th>
                        <th colspan="2">APTO</th>
                        <th colspan="2">N/APTO</th>
                    </tr>
                    <tr>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="background: #f5f5f5; font-weight: bold;">{{ $stats['total'] ?? '-' }}</td>
                        <td style="background: #f5f5f5; font-weight: bold;">{{ $stats['feminino'] ?? '-' }}</td>
                        <td>{{ $stats['desistentes'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['avaliados'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['aptos'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['nao_aptos'] ?? 0 }}</td>
                        <td></td>
                        <td rowspan="2"></td>
                        <td rowspan="2"></td>
                    </tr>
                    <tr>
                        <td style="background: #f5f5f5;">%</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['total'] > 0 ? number_format(($stats['avaliados'] / $stats['total']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['avaliados'] > 0 ? number_format(($stats['aptos'] / $stats['avaliados']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['avaliados'] > 0 ? number_format(($stats['nao_aptos'] / $stats['avaliados']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer-container">
            <p class="main-text">
                {{ $reportFooterText ?: 'FORMAÇÃO, PROFISSIONALISMO E APERFEIÇOAMENTO' }}
            </p>
            <div class="details-section">
                <p>
                    {{ strtoupper($reportInstitutionAcronym ?: substr($reportInstitutionName, 0, 3)) }}, em {{ $reportLocation ?: 'Luanda' }}, aos
                    <span class="date-line">________</span> /
                    <span class="date-line">________</span> / {{ date('Y') }}.
                </p>
                <p class="coordinator-text">
                    {{ strtoupper($reportDirectorTitle ?: 'O COORDENADOR/CHEFE DE CÁTEDRA') }}
                    <br><span class="date-line" style="margin-top: 8mm; display: inline-block;">________________________</span>
                </p>
            </div>
        </div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.focus();
                    window.print();
                }, 350);
            });
        </script>
    @endif
</body>

</html>
