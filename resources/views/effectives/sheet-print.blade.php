@php
    $isLandscape = ($printOrientation ?? 'vertical') === 'horizontal';
    $sheetWidth = $isLandscape ? '297mm' : '210mm';
    $sheetHeight = $isLandscape ? '210mm' : '297mm';
    $printedAt = now();
    $effectiveName = trim((string) $effective->full_name);
    $initials = collect(explode(' ', $effectiveName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
    $regime = \App\Models\Effective::staffTypeOptions()[$effective->staff_type] ?? $effective->staff_type;
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha do Efectivo - {{ $effectiveName }}</title>
    <style>
        @page {
            size: A4 {{ $isLandscape ? 'landscape' : 'portrait' }};
            margin: 0;
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
            font-size: {{ $isLandscape ? '8.7px' : '8.2px' }};
            overflow-x: hidden;
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
            width: {{ $sheetWidth }};
            max-width: {{ $sheetWidth }};
            min-height: {{ $sheetHeight }};
            background: #fff;
            box-shadow: {{ ! empty($embeddedMode) ? 'none' : '0 20px 60px rgba(15, 23, 42, .18)' }};
            overflow: hidden;
        }

        .sheet-inner {
            min-height: {{ $sheetHeight }};
            padding: {{ $isLandscape ? '7mm 9mm 6mm' : '7mm 8mm 6mm' }};
            display: flex;
            flex-direction: column;
            gap: {{ $isLandscape ? '6px' : '5px' }};
        }

        .sheet-top {
            text-align: center;
        }

        .sheet-logo {
            width: {{ $isLandscape ? '54px' : '48px' }};
            height: {{ $isLandscape ? '54px' : '48px' }};
            margin: 0 auto 3px;
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
            font-size: {{ $isLandscape ? '8.2px' : '7.5px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.12;
        }

        .sheet-org-name {
            margin-top: 2px;
            font-size: {{ $isLandscape ? '9.6px' : '8.6px' }};
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.12;
        }

        .sheet-rule {
            border-top: 2px solid #1f2937;
            margin: {{ $isLandscape ? '6px 0 4px' : '5px 0 3px' }};
        }

        .sheet-title {
            margin: 0;
            color: #0000ff;
            font-size: {{ $isLandscape ? '13px' : '12.5px' }};
            line-height: 1.1;
            text-align: center;
            text-transform: uppercase;
            font-weight: 800;
        }

        .summary {
            padding-bottom: {{ $isLandscape ? '6px' : '5px' }};
            border-bottom: 1px solid #111827;
        }

        .summary-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) {{ $isLandscape ? '28mm' : '24mm' }};
            gap: {{ $isLandscape ? '12px' : '8px' }};
            align-items: start;
        }

        .summary-name {
            margin: 0 0 4px;
            font-size: {{ $isLandscape ? '12px' : '10.5px' }};
            font-weight: 800;
            text-transform: uppercase;
        }

        .summary-lines {
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 2 : 1 }}, minmax(0, 1fr));
            gap: {{ $isLandscape ? '2px 10px' : '1px 8px' }};
            line-height: {{ $isLandscape ? '1.2' : '1.12' }};
        }

        .summary-lines p {
            margin: 0;
        }

        .summary-lines strong {
            font-weight: 800;
        }

        .photo-box {
            width: {{ $isLandscape ? '28mm' : '24mm' }};
            height: {{ $isLandscape ? '32mm' : '27mm' }};
            border: 1.8px solid #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
            justify-self: end;
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
            font-size: 24px;
            font-weight: 800;
        }

        .meta-grid {
            margin-top: {{ $isLandscape ? '6px' : '4px' }};
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 3 : 3 }}, minmax(0, 1fr));
            gap: {{ $isLandscape ? '5px' : '4px' }};
        }

        .meta-item {
            min-height: {{ $isLandscape ? '28px' : '24px' }};
            border: 1px solid #111827;
            padding: {{ $isLandscape ? '4px 6px' : '3px 5px' }};
            background: #fff;
        }

        .meta-label {
            display: block;
            margin-bottom: 1px;
            color: #111827;
            font-size: {{ $isLandscape ? '7.4px' : '6.4px' }};
            font-weight: 800;
            text-transform: uppercase;
        }

        .meta-value {
            display: block;
            color: #000;
            font-size: {{ $isLandscape ? '8.8px' : '7.5px' }};
            font-weight: 700;
            word-break: break-word;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat({{ $isLandscape ? 3 : 2 }}, minmax(0, 1fr));
            gap: {{ $isLandscape ? '6px' : '5px' }};
        }

        @if (! $isLandscape)
        .info-section:first-child {
            grid-row: span 2;
        }
        @endif

        .info-section {
            border: 1px solid #111827;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #fff;
        }

        .section-title {
            padding: {{ $isLandscape ? '4px 6px' : '3px 5px' }};
            background: #d9d9d9;
            color: #000;
            border-bottom: 1px solid #111827;
            font-size: {{ $isLandscape ? '7.7px' : '6.8px' }};
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table th,
        .info-table td {
            border-top: 1px solid #d1d5db;
            padding: {{ $isLandscape ? '2.5px 5px' : '2px 4px' }};
            vertical-align: top;
            line-height: {{ $isLandscape ? '1.15' : '1.08' }};
        }

        .info-table tr:first-child th,
        .info-table tr:first-child td {
            border-top: 0;
        }

        .info-table th {
            width: {{ $isLandscape ? '38%' : '40%' }};
            text-align: left;
            background: #f3f4f6;
            font-size: {{ $isLandscape ? '6.8px' : '5.9px' }};
            text-transform: uppercase;
        }

        .info-table td {
            font-size: {{ $isLandscape ? '7.8px' : '6.8px' }};
            font-weight: 600;
            word-break: break-word;
        }

        .signatures {
            margin-top: auto;
            padding-top: {{ $isLandscape ? '10px' : '8px' }};
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: {{ $isLandscape ? '55px' : '42px' }};
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
            text-align: center;
            font-size: {{ $isLandscape ? '7.8px' : '6.8px' }};
            font-weight: 700;
            min-height: {{ $isLandscape ? '26px' : '20px' }};
        }

        .sheet-footer {
            margin-top: {{ $isLandscape ? '8px' : '5px' }};
            padding-top: {{ $isLandscape ? '6px' : '4px' }};
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #475569;
            font-size: {{ $isLandscape ? '7.2px' : '6.4px' }};
            line-height: 1.25;
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
                width: {{ $sheetWidth }};
                max-width: none;
                height: auto;
                min-height: {{ $sheetHeight }};
                aspect-ratio: auto !important;
                box-shadow: none;
            }

            .sheet-inner {
                min-height: {{ $sheetHeight }};
            }

            .summary,
            .info-section {
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
                    <h1 class="sheet-title">Ficha do Efectivo</h1>
                </header>

                <section class="summary">
                    <div class="summary-header">
                        <div>
                            <h2 class="summary-name">{{ $effectiveName ?: 'Efectivo' }}</h2>
                            <div class="summary-lines">
                                <p><strong>{{ $identifierLabel }}:</strong> {{ $identifierNumber }}</p>
                                <p><strong>Regime:</strong> {{ filled($regime) ? $regime : '-' }}</p>
                                <p><strong>Posto:</strong> {{ $effective->position_label ?: '-' }}</p>
                                <p><strong>Departamento:</strong> {{ $effective->department ?: '-' }}</p>
                                <p><strong>Orgao de proveniencia:</strong> {{ $effective->placement_organ ?: '-' }}</p>
                                <p><strong>Funcao:</strong> {{ $effective->job_function ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="photo-box">
                            @if (! empty($photoUrl))
                                <img src="{{ $photoUrl }}" alt="{{ $effectiveName }}">
                            @else
                                <div class="photo-placeholder">{{ $initials ?: 'EF' }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="meta-grid">
                        @foreach ($summary as $label => $value)
                            <div class="meta-item">
                                <span class="meta-label">{{ $label }}</span>
                                <span class="meta-value">{{ filled($value) ? $value : '-' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

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
                    <div class="signature-line">Assinatura do Efectivo</div>
                    <div class="signature-line">
                        {{ $institution['director_title'] ?: 'Director' }}
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
