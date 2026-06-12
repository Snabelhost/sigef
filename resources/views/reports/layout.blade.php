@php
    $embeddedMode = $embeddedMode ?? false;
    $autoPrint = $autoPrint ?? false;
    $paperSize = strtolower($paperSize ?? 'a4');
    $paperOrientation = strtolower($paperOrientation ?? 'portrait');
    $paperKey = $paperSize.'-'.$paperOrientation;
    $sheetWidth = match ($paperKey) {
        'a3-landscape' => '420mm',
        'a3-portrait' => '297mm',
        'a4-landscape' => '297mm',
        default => '210mm',
    };
    $sheetMinHeight = match ($paperKey) {
        'a3-landscape' => '297mm',
        'a3-portrait' => '420mm',
        'a4-landscape' => '210mm',
        default => '297mm',
    };
    $reportInstitution = \App\Models\SystemSetting::getReportInstitutionConfig($institution ?? null);
    $resolveReportLogo = function (mixed $path): ?string {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! is_scalar($path)) {
            $path = null;
        }

        $path = trim((string) $path);

        if ($path !== '') {
            $decoded = json_decode($path, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $path = trim((string) (reset($decoded) ?: ''));
            }
        }

        $candidates = [];

        if ($path !== '') {
            $normalizedPath = str_replace('\\', '/', $path);

            if (\Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://', 'data:'])) {
                return $normalizedPath;
            }

            if (\Illuminate\Support\Str::startsWith($normalizedPath, '/storage/')) {
                $normalizedPath = ltrim(substr($normalizedPath, strlen('/storage/')), '/');
            } elseif (\Illuminate\Support\Str::startsWith($normalizedPath, 'storage/')) {
                $normalizedPath = ltrim(substr($normalizedPath, strlen('storage/')), '/');
            }

            $candidates[] = \Illuminate\Support\Facades\Storage::disk('public')->path($normalizedPath);
            $candidates[] = public_path($normalizedPath);
        }

        $candidates[] = public_path('images/logo-pna.png');

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || ! is_file($candidate) || ! is_readable($candidate)) {
                continue;
            }

            $mime = mime_content_type($candidate) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($candidate));
        }

        return null;
    };
    $logoSrc = $resolveReportLogo($reportInstitution['logo_path'] ?? '');
    $reportTitle = trim($__env->yieldContent('title', 'Relatorio'));
    $reportSubtitle = trim($__env->yieldContent('subtitle', ''));
    $reportTotal = $reportTotal ?? (isset($records) && is_countable($records) ? count($records) : null);
    $generatedAt = now()->format('d/m/Y H:i');
    $headerLines = array_values(array_filter([
        $reportInstitution['republic_line'] ?? null,
        $reportInstitution['ministry_line'] ?? null,
        $reportInstitution['organ_line'] ?? null,
        $reportInstitution['department_line'] ?? null,
    ]));

    if (count($headerLines) === 0) {
        $headerLines = array_values(array_filter([
            $reportInstitution['name'] ?? null,
            $reportInstitution['acronym'] ?? null,
        ]));
    }

    $footerContacts = array_values(array_filter([
        $reportInstitution['address'] ?? null,
        ! empty($reportInstitution['phone']) ? 'Tel.: '.$reportInstitution['phone'] : null,
        ! empty($reportInstitution['email']) ? 'E-mail: '.$reportInstitution['email'] : null,
        $reportInstitution['website'] ?? null,
    ]));
    $footerText = $reportInstitution['footer_text'] ?: implode(' | ', $footerContacts);
@endphp
<!DOCTYPE html>
<html class="{{ $embeddedMode ? 'report-embedded' : '' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            size: {{ strtoupper($paperSize) }} {{ $paperOrientation }};
            margin: {{ $paperKey === 'a3-landscape' ? '12mm 10mm 12mm 10mm' : ($paperOrientation === 'landscape' ? '12mm 10mm 12mm 10mm' : '14mm 12mm 14mm 12mm') }};
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: {{ $paperOrientation === 'landscape' ? '8.2px' : '8.6px' }};
            line-height: 1.25;
        }

        html.report-embedded {
            background: #e8eef6;
        }

        html.report-embedded body {
            background: #e8eef6;
            padding: 18px;
        }

        .report-sheet {
            background: #fff;
            width: 100%;
        }

        html.report-embedded .report-sheet {
            border: 1px solid #d7dfeb;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .14);
            margin: 0 auto;
            min-height: var(--report-sheet-min-height);
            overflow: visible;
            padding: {{ $paperKey === 'a3-landscape' ? '10mm 9mm 11mm' : '11mm 10mm 12mm' }};
            width: min(100%, var(--report-sheet-width));
        }

        .report-header {
            margin: 0 0 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .report-logo {
            height: {{ $paperOrientation === 'landscape' ? '46px' : '54px' }};
            margin: 0 auto 5px;
            object-fit: contain;
            width: auto;
        }

        .report-institution-lines {
            color: #111827;
            font-size: {{ $paperOrientation === 'landscape' ? '8px' : '8.8px' }};
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 6px;
        }

        .report-title {
            color: #041c4f;
            font-size: {{ $paperOrientation === 'landscape' ? '12px' : '13px' }};
            font-weight: 800;
            line-height: 1.15;
            margin: 4px 0 3px;
        }

        .report-subtitle {
            color: #374151;
            font-size: {{ $paperOrientation === 'landscape' ? '8.2px' : '8.8px' }};
            font-weight: 600;
            margin: 0 0 5px;
            text-transform: none;
        }

        .report-meta {
            color: #4b5563;
            font-size: {{ $paperOrientation === 'landscape' ? '7.8px' : '8.2px' }};
            font-weight: 600;
            margin: 0 0 8px;
            text-transform: none;
        }

        .filters-info {
            border: 1px solid #d1d5db;
            color: #374151;
            font-size: 8px;
            margin: 0 0 8px;
            padding: 5px 7px;
        }

        .summary {
            color: #111827;
            font-size: 8px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        table {
            border-collapse: collapse;
            margin: 0 0 10px;
            page-break-inside: auto;
            table-layout: fixed;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #111827;
            overflow-wrap: anywhere;
            padding: {{ $paperOrientation === 'landscape' ? '3px 4px' : '4px 5px' }};
            vertical-align: middle;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-size: {{ $paperOrientation === 'landscape' ? '7.1px' : '7.6px' }};
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        td {
            font-size: {{ $paperOrientation === 'landscape' ? '7.2px' : '7.8px' }};
        }

        tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .table-loose td {
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 800; }
        .nowrap { white-space: nowrap; }

        .badge {
            display: inline-block;
            font-size: 7px;
            font-weight: 800;
            line-height: 1;
            padding: 2px 4px;
            text-transform: uppercase;
        }

        .badge-success { color: #166534; }
        .badge-warning { color: #92400e; }
        .badge-danger { color: #991b1b; }
        .badge-info { color: #1d4ed8; }
        .badge-gray { color: #374151; }

        .group-row td {
            background: #e5e7eb !important;
            font-weight: 800;
            text-transform: uppercase;
        }

        .report-signatures {
            margin-top: 22px;
            page-break-inside: avoid;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #111827;
            display: inline-block;
            margin-top: 24px;
            padding-top: 4px;
            width: 38%;
        }

        .no-data {
            border: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 10px;
            padding: 24px;
            text-align: center;
        }

        .report-footer {
            border-top: 1px solid #d1d5db;
            bottom: 0;
            color: #4b5563;
            font-size: 7.5px;
            left: 0;
            padding-top: 4px;
            position: fixed;
            right: 0;
            text-align: center;
        }

        .report-footer .page-number:after {
            content: counter(page);
        }

        html.report-embedded .report-footer {
            margin-top: 14px;
            position: static;
        }

        @media print {
            html.report-embedded,
            html.report-embedded body {
                background: #fff;
                padding: 0;
            }

            html.report-embedded .report-sheet {
                border: 0;
                box-shadow: none;
                margin: 0;
                min-height: auto;
                padding: 0;
                width: auto;
            }
        }
    </style>
    @stack('styles')
    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.focus();
                    window.print();
                }, 250);
            });
        </script>
    @endif
</head>
<body>
    <main
        class="report-sheet"
        style="--report-sheet-width: {{ $sheetWidth }}; --report-sheet-min-height: {{ $sheetMinHeight }};"
    >
        <header class="report-header">
            @if($logoSrc)
                <img class="report-logo" src="{{ $logoSrc }}" alt="Logotipo">
            @endif
            @if(count($headerLines) > 0)
                <div class="report-institution-lines">
                    @foreach($headerLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif
            <h1 class="report-title">{{ $reportTitle }}</h1>
            @if($reportSubtitle !== '')
                <p class="report-subtitle">{{ $reportSubtitle }}</p>
            @endif
            <p class="report-meta">
                Gerado em: {{ $generatedAt }}
                @if($reportTotal !== null)
                    &nbsp; Total de registos: {{ number_format((int) $reportTotal, 0, ',', '.') }}
                @endif
            </p>
        </header>

        @hasSection('filters')
            <section class="filters-info">
                @yield('filters')
            </section>
        @endif

        @hasSection('summary')
            <section class="summary">
                @yield('summary')
            </section>
        @endif

        @yield('content')

        <footer class="report-footer">
            {{ $footerText ?: ($reportInstitution['name'] ?? 'SIGEF') }}
            <span> | Pagina <span class="page-number"></span></span>
        </footer>
    </main>
</body>
</html>
