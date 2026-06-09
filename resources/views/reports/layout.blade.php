<!DOCTYPE html>
<html>

<head>
    @php
        $reportInstitution = \App\Models\SystemSetting::getReportInstitutionConfig($institution ?? null);
        $logoPath = $reportInstitution['logo_path'] ?? '';
        $logoAbsolutePath = $logoPath
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath)
            : public_path('images/logo-pna.png');
        $logoExists = is_file($logoAbsolutePath);
        $headerLines = array_filter([
            $reportInstitution['republic_line'] ?? null,
            $reportInstitution['ministry_line'] ?? null,
            $reportInstitution['organ_line'] ?? null,
            $reportInstitution['department_line'] ?? null,
        ]);
        $footerContacts = array_filter([
            $reportInstitution['address'] ?? null,
            ! empty($reportInstitution['phone']) ? 'Tel.: '.$reportInstitution['phone'] : null,
            $reportInstitution['email'] ?? null,
            $reportInstitution['website'] ?? null,
        ]);
        $footerText = $reportInstitution['footer_text']
            ?: implode(' | ', array_filter([$reportInstitution['name'] ?? 'SIGEF', ...$footerContacts]));
    @endphp
    <meta charset="utf-8">
    <title>@yield('title', 'Relatório SIGEF')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            padding: 10px 20px;
        }

        @page {
            margin: 20mm 18mm 18mm 18mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #041c4f;
            padding-bottom: 12px;
        }

        .header-logo {
            margin-bottom: 8px;
        }

        .header-logo img {
            height: 60px;
        }

        .header-lines {
            color: #041c4f;
            font-size: 10px;
            line-height: 1.35;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .header-institution {
            font-size: 13px;
            color: #041c4f;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .header h1 {
            font-size: 14px;
            color: #041c4f;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        .header h2 {
            font-size: 12px;
            color: #555;
            margin-top: 3px;
        }

        .header .subtitle {
            font-size: 10px;
            color: #777;
            margin-top: 4px;
        }

        .filters-info {
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 14px;
            font-size: 10px;
            color: #555;
        }

        .filters-info strong {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table th {
            background: #041c4f;
            color: white;
            padding: 7px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table tr:nth-child(even) td {
            background: #f9fafb;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        .footer .page-number:after {
            content: counter(page);
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #374151;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: 600;
        }

        .summary {
            margin-bottom: 14px;
            padding: 10px 14px;
            background: #eff6ff;
            border-left: 3px solid #041c4f;
            border-radius: 4px;
        }

        .summary span {
            font-size: 11px;
            font-weight: 600;
            color: #041c4f;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="header">
        @if($logoExists)
            <div class="header-logo">
                <img src="{{ $logoAbsolutePath }}" alt="Logotipo">
            </div>
        @endif
        @if(count($headerLines) > 0)
            <div class="header-lines">
                @foreach($headerLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        @endif
        <div class="header-institution">{{ $reportInstitution['name'] ?? 'SIGEF' }}</div>
        <h1>@yield('title', 'Relatório')</h1>
        <h2>{{ $reportInstitution['acronym'] ?? 'SIGEF' }}</h2>
        <p class="subtitle">Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @hasSection('filters')
    <div class="filters-info">
        @yield('filters')
    </div>
    @endif

    @hasSection('summary')
    <div class="summary">
        @yield('summary')
    </div>
    @endif

    @yield('content')

    <div class="footer">
        <span>{{ $footerText }} | {{ now()->format('d/m/Y H:i') }} | Página <span class="page-number"></span></span>
    </div>
</body>

</html>
