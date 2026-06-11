@php
    $isLandscape = ($printOrientation ?? 'vertical') === 'horizontal';
    $sheetWidth = $isLandscape ? '297mm' : '210mm';
    $sheetHeight = $isLandscape ? '210mm' : '297mm';
    $sheetAspectRatio = $isLandscape ? '297 / 210' : '210 / 297';
    $printedAt = now();
    $academicYear = $summary['Ano academico'] ?? null;
    $titleYear = filled($academicYear) && $academicYear !== '-'
        ? ' DO ANO: '.$academicYear
        : '';
    $studentName = $candidate?->full_name ?: $student->full_name;
    $documentPageTitle = $documentPageTitle ?? 'Ficha de Inscricao - '.$studentName;
    $documentTitle = $documentTitle ?? 'Ficha de Inscricao'.$titleYear;
    $documentIntro = $documentIntro ?? 'Vimos por este meio apresentar o(a) formando(a) abaixo identificado(a), titular da presente ficha de inscricao, contendo os dados pessoais, o enquadramento academico e as disciplinas registadas no SIGEF.';
    $recordsTitle = $recordsTitle ?? 'Disciplinas inscritas';
    $recordEmptyText = $recordEmptyText ?? 'Nenhuma disciplina registada.';
    $recordColumns = $recordColumns ?? [
        ['key' => 'index', 'label' => '#', 'width' => '7%', 'class' => 'center'],
        ['key' => 'academic_year', 'label' => 'Ano lectivo', 'width' => '17%', 'class' => 'center'],
        ['key' => 'phase', 'label' => 'Fase', 'width' => '16%', 'class' => 'center'],
        ['key' => 'class', 'label' => 'Turma', 'width' => '16%', 'class' => 'center'],
        ['key' => 'subject', 'label' => 'Disciplina'],
    ];
    $signatureBlocks = $signatureBlocks ?? [
        ['Assinatura do Formando'],
        array_values(array_filter([
            $institution['director_title'] ?: 'Responsavel',
            $institution['director_name'] ?? null,
        ])),
    ];
    $initials = collect(explode(' ', trim((string) $studentName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentPageTitle }}</title>
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
            background: {{ ! empty($embeddedMode) ? '#dfe3e8' : '#e5e7eb' }};
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $isLandscape ? '9px' : '9.4px' }};
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
            padding: {{ $isLandscape ? '8mm 11mm 7mm' : '9mm 11mm 8mm' }};
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sheet-top {
            text-align: center;
        }

        .sheet-logo {
            width: {{ $isLandscape ? '50px' : '58px' }};
            height: {{ $isLandscape ? '50px' : '58px' }};
            margin: 0 auto 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sheet-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .sheet-header-lines {
            font-size: {{ $isLandscape ? '9px' : '9.5px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .sheet-org-name {
            margin-top: 3px;
            font-size: {{ $isLandscape ? '10px' : '10.7px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.18;
        }

        .sheet-rule {
            border-top: 2px solid #1f2937;
            margin: 7px 0 4px;
        }

        .sheet-title {
            margin: 0;
            color: #0000ff;
            font-size: {{ $isLandscape ? '13px' : '15px' }};
            line-height: 1.1;
            text-align: center;
            text-transform: uppercase;
            font-weight: 800;
        }

        .identity-block {
            display: grid;
            grid-template-columns: minmax(0, 1fr) {{ $isLandscape ? '28mm' : '25mm' }};
            gap: {{ $isLandscape ? '12px' : '10px' }};
            align-items: start;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
        }

        .summary-item {
            height: 28px;
            padding: 4px 6px;
            border: 1px solid #111827;
            vertical-align: top;
        }

        .summary-item.is-empty {
            color: transparent;
        }

        .photo-box {
            width: {{ $isLandscape ? '28mm' : '25mm' }};
            height: {{ $isLandscape ? '32mm' : '30mm' }};
            border: 1.6px solid #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5eefc;
            color: #06245f;
            font-size: 22px;
            font-weight: 800;
        }

        .label {
            display: block;
            color: #000;
            font-size: 7.4px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
            margin-bottom: 2px;
        }

        .value {
            display: block;
            color: #000;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.22;
            word-break: break-word;
        }

        .value-blue {
            color: #0000ff;
        }

        .intro {
            margin: 3px 0 5px;
            line-height: 1.35;
            text-align: justify;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 3 : 2 }}, minmax(0, 1fr));
            gap: 6px;
        }

        .info-section {
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
            font-size: 8px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table th,
        .info-table td {
            border-top: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: top;
            line-height: 1.2;
        }

        .info-table tr:first-child th,
        .info-table tr:first-child td {
            border-top: 0;
        }

        .info-table th {
            width: 36%;
            text-align: left;
            background: #f3f4f6;
            font-size: 7.3px;
            text-transform: uppercase;
        }

        .info-table td {
            font-weight: 600;
            word-break: break-word;
        }

        .subjects {
            border: 1px solid #111827;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .subjects table {
            width: 100%;
            border-collapse: collapse;
        }

        .subjects th,
        .subjects td {
            border: 1px solid #111827;
            padding: {{ $isLandscape ? '2.5px 4px' : '3px 4px' }};
            line-height: 1.15;
        }

        .subjects th {
            background: #d9d9d9;
            text-align: center;
            font-size: 7.6px;
            text-transform: uppercase;
        }

        .subjects td {
            font-weight: 600;
        }

        .subjects .center {
            text-align: center;
        }

        .signatures {
            margin-top: auto;
            padding-top: {{ $isLandscape ? '10px' : '18px' }};
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 55px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 5px;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            min-height: 28px;
        }

        .sheet-footer {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #475569;
            font-size: 7.5px;
            line-height: 1.35;
        }

        .footer-right {
            text-align: right;
            white-space: nowrap;
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
            .subjects,
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
                            <img src="{{ $institution['logo_url'] }}" alt="Logotipo">
                        @endif
                    </div>

                    @if (! empty($institution['header_lines']))
                        <div class="sheet-header-lines">
                            @foreach ($institution['header_lines'] as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="sheet-org-name">
                        {{ $institution['name'] ?? 'SIGEF' }}
                        @if (filled($institution['subtitle'] ?? null))
                            "{{ $institution['subtitle'] }}"
                        @endif
                    </div>

                    <div class="sheet-rule"></div>
                    <h1 class="sheet-title">{{ $documentTitle }}</h1>
                </header>

                <section class="identity-block">
                    <table class="summary">
                        <tbody>
                            @foreach (collect($summary)->chunk($isLandscape ? 4 : 2) as $row)
                                <tr>
                                    @foreach ($row as $label => $value)
                                        <td class="summary-item">
                                            <span class="label">{{ $label }}</span>
                                            <span class="value {{ in_array($label, ['NURI/NIP', 'Curso', 'Nome'], true) ? 'value-blue' : '' }}">
                                                {{ filled($value) ? $value : '-' }}
                                            </span>
                                        </td>
                                    @endforeach

                                    @for ($i = $row->count(); $i < ($isLandscape ? 4 : 2); $i++)
                                        <td class="summary-item is-empty">&nbsp;</td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="photo-box">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Foto do formando">
                        @else
                            <div class="photo-placeholder">{{ $initials ?: 'F' }}</div>
                        @endif
                    </div>
                </section>

                <p class="intro">{{ $documentIntro }}</p>

                <section class="section-grid">
                    @foreach ($sections as $title => $rows)
                        <div class="info-section">
                            <div class="section-title">{{ $title }}</div>
                            <table class="info-table">
                                <tbody>
                                    @foreach ($rows as $label => $value)
                                        <tr>
                                            <th>{{ $label }}</th>
                                            <td>{{ filled($value) ? $value : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </section>

                <section class="subjects">
                    <div class="section-title">{{ $recordsTitle }}</div>
                    <table>
                        <thead>
                            <tr>
                                @foreach ($recordColumns as $column)
                                    <th @if (! empty($column['width'])) style="width: {{ $column['width'] }};" @endif>
                                        {{ $column['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjectRows as $row)
                                <tr>
                                    @foreach ($recordColumns as $column)
                                        <td class="{{ $column['class'] ?? '' }}">
                                            {{ filled($row[$column['key']] ?? null) ? $row[$column['key']] : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($recordColumns) }}" class="center">{{ $recordEmptyText }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <section class="signatures">
                    @foreach ($signatureBlocks as $signatureBlock)
                        <div class="signature-line">
                            @foreach ((array) $signatureBlock as $signatureLine)
                                @if (! $loop->first)<br>@endif
                                {{ $signatureLine }}
                            @endforeach
                        </div>
                    @endforeach
                </section>

                <footer class="sheet-footer">
                    <div>{{ $institution['footer'] ?: ($institution['name'] ?? 'SIGEF') }}</div>
                    <div class="footer-right">
                        <div>Data de impressao: {{ $printedAt->format('d/m/Y') }}</div>
                        <div>Hora: {{ $printedAt->format('H:i:s') }}</div>
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
