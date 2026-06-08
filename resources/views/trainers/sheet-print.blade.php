@php
    $isLandscape = ($printOrientation ?? 'horizontal') === 'horizontal';
    $sheetWidth = $isLandscape ? '297mm' : '210mm';
    $sheetHeight = $isLandscape ? '210mm' : '297mm';
    $sheetAspectRatio = $isLandscape ? '297 / 210' : '210 / 297';
    $identifierLabel = filled($trainer->bilhete) ? 'N&ordm; DO BI' : 'NIP';
    $identifierNumber = trim((string) ($trainer->bilhete ?: $trainer->nip ?: '-'));
    $rankLabel = trim((string) ($trainer->rank?->name ?: '')) ?: '-';
    $printedAt = now();
    $initials = collect(explode(' ', trim((string) $trainer->full_name)))
        ->filter()
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $summaryCards = [
        'Disciplinas' => $teachingSummary['subjects'] ?? '0',
        'Turmas' => $teachingSummary['classes'] ?? '0',
        'Tempos semanais' => $teachingSummary['slots'] ?? '0',
        'Carga semanal' => $teachingSummary['weekly_load'] ?? '0 h',
        'Tempos activos' => $teachingSummary['active_slots'] ?? '0',
    ];
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha do Professor - {{ $trainer->full_name }}</title>
    <style>
        @page {
            size: A4 {{ $isLandscape ? 'landscape' : 'portrait' }};
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: {{ ! empty($embeddedMode) ? '#ffffff' : '#e5e7eb' }};
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $isLandscape ? '9.2px' : '9.5px' }};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 100%;
            min-height: 100vh;
            padding: {{ ! empty($embeddedMode) ? '0' : '18px' }};
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .sheet {
            width: 100%;
            max-width: {{ $sheetWidth }};
            min-height: {{ $sheetHeight }};
            aspect-ratio: {{ $sheetAspectRatio }};
            background: #fff;
            box-shadow: {{ ! empty($embeddedMode) ? 'none' : '0 20px 60px rgba(15, 23, 42, .18)' }};
            overflow: visible;
        }

        .sheet-inner {
            min-height: {{ $sheetHeight }};
            padding: {{ $isLandscape ? '6mm 7mm 5mm' : '7mm 8mm 6mm' }};
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sheet-top {
            text-align: center;
        }

        .sheet-logo {
            width: {{ $isLandscape ? '70px' : '80px' }};
            height: {{ $isLandscape ? '70px' : '80px' }};
            margin: 0 auto 2px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sheet-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .sheet-org-name {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .sheet-org-subtitle {
            margin-top: 2px;
            font-size: 9.5px;
            font-weight: 700;
        }

        .sheet-org-document {
            margin-top: 3px;
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .sheet-rule {
            border-top: 2px solid #1f2937;
            margin: 6px 0 4px;
        }

        .sheet-title {
            margin: 0;
            font-size: 15px;
            line-height: 1.1;
            text-align: center;
            text-transform: uppercase;
            color: #0000ff;
            font-weight: 800;
        }

        .summary {
            padding-bottom: 6px;
            border-bottom: 1px solid #111827;
        }

        .summary-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) {{ $isLandscape ? '94px' : '90px' }};
            gap: 8px;
            align-items: start;
        }

        .summary-name {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .summary-lines {
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 2 : 1 }}, minmax(0, 1fr));
            gap: 2px 10px;
            line-height: 1.25;
        }

        .summary-lines p {
            margin: 0;
        }

        .summary-lines strong {
            font-weight: 800;
        }

        .trainer-photo {
            width: {{ $isLandscape ? '90px' : '86px' }};
            height: {{ $isLandscape ? '96px' : '92px' }};
            border: 2px solid #1f2937;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            justify-self: end;
        }

        .trainer-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f2f6d;
            font-size: 28px;
            font-weight: 800;
            background: #f3f4f6;
        }

        .meta-grid {
            margin-top: 6px;
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 5 : 3 }}, minmax(0, 1fr));
            gap: 5px;
        }

        .meta-item,
        .metric-card {
            border: 1px solid #111827;
            padding: 4px 6px;
            min-height: 30px;
            background: #fff;
        }

        .meta-label,
        .metric-label {
            display: block;
            font-size: 7.8px;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
            margin-bottom: 2px;
        }

        .meta-value,
        .metric-value {
            display: block;
            font-size: 9.2px;
            font-weight: 700;
            color: #000;
            word-break: break-word;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 5px;
        }

        .metric-card {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .metric-value {
            font-size: 12px;
            font-weight: 800;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 2 : 1 }}, minmax(0, 1fr));
            gap: 6px;
        }

        .info-section,
        .teaching-section {
            border: 1px solid #111827;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #fff;
        }

        .section-title {
            padding: 4px 6px;
            background: #d9d9d9;
            color: #000;
            border-bottom: 1px solid #111827;
            font-size: 8.8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .info-table,
        .teaching-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table th,
        .info-table td,
        .teaching-table th,
        .teaching-table td {
            border-top: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
            line-height: 1.25;
        }

        .info-table th {
            width: 39%;
            text-align: left;
            color: #111827;
            background: #f3f4f6;
            font-size: 7.8px;
            text-transform: uppercase;
        }

        .info-table td {
            color: #111827;
            font-weight: 600;
            word-break: break-word;
        }

        .teaching-section {
            page-break-inside: auto;
            break-inside: auto;
        }

        .teaching-table {
            table-layout: auto;
            font-size: {{ $isLandscape ? '7.5px' : '7px' }};
        }

        .teaching-table th {
            background: #f3f4f6;
            text-transform: uppercase;
            text-align: left;
            font-size: {{ $isLandscape ? '7px' : '6.5px' }};
            color: #111827;
        }

        .teaching-table td {
            font-weight: 600;
            color: #111827;
        }

        .empty-state {
            padding: 10px;
            color: #475569;
            font-weight: 700;
        }

        .sheet-footer {
            margin-top: auto;
            padding-top: 7px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #475569;
            font-size: 8px;
            line-height: 1.35;
        }

        .footer-left,
        .footer-right {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .footer-right {
            text-align: right;
        }

        @media print {
            html,
            body {
                background: #fff;
                width: {{ $sheetWidth }};
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            .page {
                padding: 0;
                min-height: auto;
                display: block;
            }

            .sheet {
                width: 100%;
                max-width: none;
                height: auto;
                min-height: auto;
                aspect-ratio: auto !important;
                box-shadow: none;
            }

            .sheet-inner {
                min-height: {{ $sheetHeight }};
            }

            .info-section,
            .summary {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="{{ ! empty($embeddedMode) ? 'embedded-print-preview' : '' }}">
    <main class="page">
        <article class="sheet">
            <div class="sheet-inner">
                <header class="sheet-top">
                    <div class="sheet-logo">
                        @if (! empty($institution['logo_url']))
                            <img src="{{ $institution['logo_url'] }}" alt="SIGEF">
                        @endif
                    </div>
                    <div class="sheet-org-name">{{ $institution['name'] ?? 'Sistema Integrado de Gestao de Formacao' }}</div>
                    @if (filled($institution['subtitle'] ?? null))
                        <div class="sheet-org-subtitle">{{ $institution['subtitle'] }}</div>
                    @endif
                    @if (filled($institution['report_header'] ?? null))
                        <div class="sheet-org-document">{{ $institution['report_header'] }}</div>
                    @endif
                    <div class="sheet-rule"></div>
                    <h1 class="sheet-title">Ficha do Professor</h1>
                </header>

                <section class="summary">
                    <div class="summary-header">
                        <div>
                            <div class="summary-name">{{ $trainer->full_name }}</div>
                            <div class="summary-lines">
                                <p><strong>{!! $identifierLabel !!}:</strong> {{ $identifierNumber }}</p>
                                <p><strong>Regime:</strong> {{ $sections['Identificacao']['Regime'] ?? '-' }}</p>
                                <p><strong>Patente:</strong> {{ $rankLabel }}</p>
                                <p><strong>Grau academico:</strong> {{ $trainer->education_level ?: '-' }}</p>
                                <p><strong>Especializacao:</strong> {{ $trainer->specialization ?: '-' }}</p>
                                <p><strong>Departamento:</strong> {{ $trainer->department ?: '-' }}</p>
                                <p><strong>Orgao de proveniencia:</strong> {{ $trainer->organ ?: '-' }}</p>
                                <p><strong>Funcao:</strong> {{ $trainer->job_function ?: '-' }}</p>
                            </div>
                        </div>
                        <div class="trainer-photo">
                            @if (! empty($photoUrl))
                                <img src="{{ $photoUrl }}" alt="{{ $trainer->full_name }}">
                            @else
                                <div class="photo-fallback">{{ $initials ?: 'PR' }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="meta-grid">
                        @foreach ([
                            $identifierLabel => $identifierNumber,
                            'Data de admissao' => $sections['Dados profissionais']['Data de admissao'] ?? '-',
                            'Disciplinas' => $teachingSummary['subjects'] ?? '0',
                            'Tempos activos' => $teachingSummary['active_slots'] ?? '0',
                            'Carga semanal' => $teachingSummary['weekly_load'] ?? '0 h',
                        ] as $label => $value)
                            <div class="meta-item">
                                <span class="meta-label">{!! $label !!}</span>
                                <span class="meta-value">{{ filled($value) ? $value : '-' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="metrics">
                    @foreach ($summaryCards as $label => $value)
                        <div class="metric-card">
                            <span class="metric-label">{{ $label }}</span>
                            <span class="metric-value">{{ filled($value) ? $value : '-' }}</span>
                        </div>
                    @endforeach
                </section>

                <section class="section-grid">
                    @foreach ($sections as $title => $rows)
                        <div class="info-section">
                            <div class="section-title">{{ $title }}</div>
                            <table class="info-table">
                                <tbody>
                                    @foreach ($rows as $label => $value)
                                        <tr>
                                            <th>{!! $label !!}</th>
                                            <td>{{ filled($value) ? $value : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </section>

                <section class="teaching-section">
                    <div class="section-title">Disciplinas, turmas e tempos lectivos</div>
                    @if (count($teachingRows) === 0)
                        <div class="empty-state">Nenhuma atribuicao de disciplina ou tempo lectivo registada.</div>
                    @else
                        <table class="teaching-table">
                            <thead>
                                <tr>
                                    <th>Ano lectivo</th>
                                    <th>Curso</th>
                                    <th>Ano</th>
                                    <th>Turno</th>
                                    <th>Turma</th>
                                    <th>Disciplina</th>
                                    <th>Dia</th>
                                    <th>Horario</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teachingRows as $row)
                                    <tr>
                                        <td>{{ $row['academic_year'] }}</td>
                                        <td>{{ $row['course'] }}</td>
                                        <td>{{ $row['frequency_year'] }}</td>
                                        <td>{{ $row['shift'] }}</td>
                                        <td>{{ $row['class'] }}</td>
                                        <td>{{ $row['subject'] }}</td>
                                        <td>{{ $row['weekday'] }}</td>
                                        <td>{{ $row['time'] }}</td>
                                        <td>{{ $row['lesson_type'] }}</td>
                                        <td>{{ $row['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                <footer class="sheet-footer">
                    <div class="footer-left">
                        <span>{{ $institution['address'] ?? '' }}</span>
                        <span>Tel.: {{ $institution['phone'] ?? '' }}</span>
                        <span>Email: {{ $institution['email'] ?? '' }}</span>
                        @if (filled($institution['footer'] ?? null))
                            <span>{{ $institution['footer'] }}</span>
                        @endif
                    </div>
                    <div class="footer-right">
                        <span>Data de impressao: {{ $printedAt->format('d/m/Y') }}</span>
                        <span>Hora: {{ $printedAt->format('H:i:s') }}</span>
                        <span>Formato: A4 {{ $isLandscape ? 'horizontal' : 'vertical' }}</span>
                    </div>
                </footer>
            </div>
        </article>
    </main>

    @if (! empty($autoPrint))
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
