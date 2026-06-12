<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Pauta Geral - {{ $turma->name ?? 'Turma' }}</title>
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
        $disciplineCount = isset($disciplinas) ? $disciplinas->count() : 0;
        $subjectColumnWidth = $disciplineCount > 18 ? '6.6mm' : ($disciplineCount > 12 ? '8.2mm' : '10mm');
        $subjectHeaderFontSize = $disciplineCount > 18 ? '6.2px' : ($disciplineCount > 12 ? '7px' : '7.6px');
        $tableFontSize = $disciplineCount > 18 ? '7.2px' : '8.2px';
    @endphp
    <style>
        @page {
            size: A3 landscape;
            margin: 7mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            background: {{ $embeddedMode ? '#edf2f7' : '#fff' }};
            color: #000;
            padding: {{ $embeddedMode ? '6mm 0' : '0' }};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 7mm 8mm 6mm;
            background: #fff;
            overflow: visible;
        }

        body.embedded-print-preview .container {
            width: min(420mm, calc(100vw - 16mm));
            min-height: 297mm;
            aspect-ratio: 420 / 297;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-shadow: 0 16px 45px rgba(15, 23, 42, .12);
        }

        .container-logo {
            text-align: center;
            margin-bottom: 1.2mm;
        }

        .container-logo img {
            width: 50px;
            height: auto;
        }

        .conteiner {
            text-align: center;
            font-size: 11px;
            line-height: 1.28;
            margin-bottom: 2.8mm;
        }

        .conteiner strong {
            font-size: 12px;
        }

        .conteiner p strong {
            font-size: 15px;
            letter-spacing: 1px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 4mm;
            margin-bottom: 2.4mm;
            font-size: 8.8px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1mm;
            min-width: 0;
        }

        .info-item label {
            flex-shrink: 0;
            font-weight: bold;
        }

        .info-item .value {
            min-width: 24mm;
            max-width: 126mm;
            overflow: hidden;
            padding: 0 1mm;
            border-bottom: 1px solid #000;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table-wrap {
            width: 100%;
            overflow: visible;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: {{ $tableFontSize }};
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }

        th {
            background: #fff;
            font-size: 7.8px;
            font-weight: bold;
        }

        .th-disciplina {
            width: {{ $subjectColumnWidth }};
            height: 34mm;
            padding: 1px;
            background: #f5f5f5;
            font-size: {{ $subjectHeaderFontSize }};
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            writing-mode: vertical-lr;
        }

        td.nome {
            max-width: 76mm;
            overflow: hidden;
            padding-left: 3px;
            font-size: 8.4px;
            text-align: left;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .col-num {
            width: 8mm;
        }

        .col-nome {
            width: 76mm;
        }

        .col-media {
            width: 14mm;
        }

        .col-resultado {
            width: 24mm;
            font-size: 7px;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .aprovado {
            color: green;
            font-weight: bold;
        }

        .reprovado {
            color: red;
            font-weight: bold;
        }

        .pendente {
            color: #666;
            font-weight: bold;
        }

        .footer {
            margin-top: 6mm;
            font-size: 8.2px;
            page-break-inside: avoid;
        }

        .slogan {
            margin: 2.4mm 0 1.8mm;
            font-size: 8.2px;
            font-style: italic;
            text-align: center;
        }

        .footer-text {
            margin-bottom: 3mm;
            font-size: 8.2px;
            text-align: center;
        }

        .assinatura-row {
            display: flex;
            justify-content: space-between;
            margin-top: 8mm;
            padding: 0 18mm;
        }

        .assinatura {
            width: 30%;
            text-align: center;
        }

        .assinatura-linha {
            margin-top: 7mm;
            padding-top: 0.8mm;
            border-top: 1px solid #000;
            font-size: 8px;
        }

        @media print {
            html,
            body {
                background: #fff;
                padding: 0;
            }

            .container {
                width: 100%;
                padding: 0;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                min-height: auto !important;
            }

            thead {
                display: table-header-group;
            }

            tr,
            .footer {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="{{ $embeddedMode ? 'embedded-print-preview' : '' }}">
    <div class="container">
        <div class="container-logo">
            <img src="{{ $reportLogoUrl }}" alt="Logotipo">
        </div>

        <div class="conteiner">
            @foreach($reportHeaderLines as $line)
                {{ $reportUpper($line) }}<br>
            @endforeach
            <p><strong>PAUTA GERAL DE CLASSIFICAÇÃO</strong></p>
        </div>

        <div class="info-row">
            <div class="info-item">
                <label>Turma:</label>
                <span class="value">{{ $turma->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Curso:</label>
                <span class="value">{{ $curso ?? $turma->courseMap?->course?->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Ano Lect.:</label>
                <span class="value">{{ $anoLectivo ?? $turma->academicYear?->year ?? date('Y') }}</span>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-num">Nº</th>
                        <th class="col-nome">NOME DO FORMANDO</th>
                        @foreach($disciplinas as $disciplina)
                            <th class="th-disciplina">{{ strtoupper($disciplina->name) }}</th>
                        @endforeach
                        <th class="th-disciplina col-media">MÉDIA GERAL</th>
                        <th class="th-disciplina col-resultado">RESULTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alunos as $index => $aluno)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="nome">{{ $aluno['nome'] }}</td>
                            @foreach($disciplinas as $disciplina)
                                <td>{{ $aluno['medias'][$disciplina->id] ?? '-' }}</td>
                            @endforeach
                            <td style="font-weight: bold;">{{ $aluno['media_geral'] ?? '-' }}</td>
                            <td class="{{ ($aluno['resultado'] ?? '') == 'Aprovado' ? 'aprovado' : (($aluno['resultado'] ?? '') == 'Pendente' ? 'pendente' : 'reprovado') }}">
                                {{ strtoupper($aluno['resultado'] ?? '-') }}
                            </td>
                        </tr>
                    @empty
                        @for($i = 1; $i <= 50; $i++)
                            <tr>
                                <td>{{ $i }}</td>
                                <td class="nome"></td>
                                @foreach($disciplinas as $disciplina)
                                    <td></td>
                                @endforeach
                                <td></td>
                                <td></td>
                            </tr>
                        @endfor
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div class="slogan">"{{ $reportFooterText ?: 'FORMAÇÃO, PROFISSIONALISMO E APERFEIÇOAMENTO' }}"</div>

            <div class="footer-text">
                {{ strtoupper($reportInstitutionAcronym ?: substr($reportInstitutionName, 0, 3)) }},
                em {{ $reportLocation ?: 'Luanda' }}, aos ___/___/{{ date('Y') }}.
            </div>

            <div class="assinatura-row">
                <div class="assinatura">
                    <div class="assinatura-linha">O COORDENADOR PEDAGÓGICO</div>
                </div>
                <div class="assinatura">
                    <div class="assinatura-linha">O DIRECTOR ADJ. P/ INSTRUÇÃO</div>
                </div>
                <div class="assinatura">
                    <div class="assinatura-linha">{{ strtoupper($reportDirectorTitle ?: 'O DIRECTOR DA ESCOLA') }}</div>
                </div>
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
