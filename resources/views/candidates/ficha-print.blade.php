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
    $summaryColumns = $isLandscape ? 6 : 3;
    $summaryRows = collect($summary)->chunk($summaryColumns);
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha de Inscricao - {{ $candidate->full_name }}</title>
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
            font-size: {{ $isLandscape ? '9.2px' : '9.4px' }};
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
            padding: {{ $isLandscape ? '8mm 11mm 7mm' : '10mm 12mm 8mm' }};
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sheet-top {
            text-align: center;
        }

        .sheet-logo {
            width: {{ $isLandscape ? '52px' : '60px' }};
            height: {{ $isLandscape ? '52px' : '60px' }};
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
            font-size: {{ $isLandscape ? '9.2px' : '9.6px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .sheet-org-name {
            margin-top: 3px;
            font-size: {{ $isLandscape ? '9.8px' : '10.4px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
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

        .intro {
            margin: 4px 0 5px;
            line-height: 1.35;
            text-align: justify;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
        }

        .summary-cell {
            height: 30px;
            padding: 4px 6px;
            border: 1px solid #111827;
            vertical-align: top;
        }

        .summary-cell.is-empty {
            color: transparent;
        }

        .label {
            display: block;
            color: #000;
            font-size: 7.5px;
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
            line-height: 1.25;
            word-break: break-word;
        }

        .value-blue {
            color: #0000ff;
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

        .info-table,
        .info-table th,
        .info-table td {
            border-top: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: top;
            line-height: 1.22;
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

        .signatures {
            margin-top: auto;
            padding-top: {{ $isLandscape ? '12px' : '22px' }};
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
                    <h1 class="sheet-title">Ficha de Inscricao{{ $titleYear }}</h1>
                </header>

                <table class="summary" role="presentation">
                    <tbody>
                        @foreach ($summaryRows as $row)
                            <tr>
                                @foreach ($row as $label => $value)
                                    <td class="summary-cell">
                                        <span class="label">{{ $label }}</span>
                                        <span class="value {{ in_array($label, ['NIP', 'N.o do BI', 'Curso', 'Nome'], true) ? 'value-blue' : '' }}">
                                            {{ filled($value) ? $value : '-' }}
                                        </span>
                                    </td>
                                @endforeach

                                @for ($i = $row->count(); $i < $summaryColumns; $i++)
                                    <td class="summary-cell is-empty">&nbsp;</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="intro">
                    Vimos por este meio apresentar o(a) formando(a) abaixo identificado(a), titular da presente ficha de inscricao,
                    contendo os dados registados no Sistema Integrado de Gestao Escolar e Formacao.
                </p>

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

                <section class="signatures">
                    <div class="signature-line">Assinatura do Formando</div>
                    <div class="signature-line">
                        {{ $institution['director_title'] ?: 'Responsavel' }}
                        @if (filled($institution['director_name'] ?? null))
                            <br>{{ $institution['director_name'] }}
                        @endif
                    </div>
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
