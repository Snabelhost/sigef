@php
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
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ponto de Presenças</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #333;
            padding: 5px 10px;
        }

        @page {
            size: landscape;
            margin: 12mm 10mm 10mm 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #041c4f;
            padding-bottom: 8px;
        }

        .header-logo img {
            height: 45px;
        }

        .header h1 {
            font-size: 12px;
            color: #041c4f;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .header h2 {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .header .subtitle {
            font-size: 8px;
            color: #777;
            margin-top: 3px;
        }

        .filters-info {
            background: #f3f4f6;
            padding: 5px 10px;
            border-radius: 3px;
            margin-bottom: 8px;
            font-size: 9px;
            color: #555;
        }

        .filters-info strong {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            text-align: center;
        }

        table th {
            background: #041c4f;
            color: white;
            padding: 3px 2px;
            font-size: 7px;
            font-weight: 600;
        }

        table td {
            padding: 2px 1px;
            font-size: 7px;
        }

        .col-num {
            width: 22px;
        }

        .col-name {
            width: 140px;
            text-align: left !important;
            padding-left: 4px !important;
        }

        .col-day {
            width: auto;
        }



        .status-P {
            color: #166534;
            font-weight: 700;
        }

        .status-F {
            color: #991b1b;
            font-weight: 700;
        }

        .status-J {
            color: #854d0e;
            font-weight: 600;
        }

        .status-A {
            color: #1e40af;
            font-weight: 600;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        tr:nth-child(even) td.col-total {
            background: #e8e8e8;
        }

        .legend {
            margin-top: 6px;
            font-size: 8px;
            color: #555;
        }

        .legend span {
            margin-right: 12px;
        }

        .legend .lbl {
            font-weight: 600;
        }

        .summary-row td {
            background: #e8f0fe !important;
            font-weight: 600;
            font-size: 7px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 3px;
        }

        .footer .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        @if($logoSrc)
            <div class="header-logo">
                <img src="{{ $logoSrc }}" alt="Logotipo">
            </div>
        @endif
        <h1>Ponto de Presenças</h1>
        <h2>{{ $reportInstitution['name'] ?? 'Sistema Integrado de Gestão Escolar e Formação' }}</h2>
        <p class="subtitle">Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="filters-info">
        @if($institution) <strong>Instituição:</strong> {{ $institution->name }} @endif
        @if($cia) | <strong>CIA:</strong> {{ $cia }} @endif
        | <strong>Período:</strong> {{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}
        | <strong>Total de dias:</strong> {{ $totalDays }}
    </div>

    @if(count($students) > 0)
    <table>
        <thead>
            <tr>
                <th class="col-num">Nº</th>
                <th class="col-name">Nome do Formando</th>
                @foreach($days as $day)
                <th class="col-day">{{ $day->format('d') }}<br><small>{{ $dayNames[$day->format('N')] }}</small></th>
                @endforeach

            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $student)
            @php
            $studentId = $student->id;
            $attendances = $attendanceMap[$studentId] ?? [];
            $totals = ['P' => 0, 'F' => 0, 'J' => 0, 'A' => 0];
            @endphp
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-name">{{ $student->candidate?->full_name ?? $student->full_name ?? '-' }}</td>
                @foreach($days as $day)
                @php
                $dateKey = $day->format('Y-m-d');
                $status = $attendances[$dateKey] ?? '';
                if ($status && isset($totals[$status])) {
                $totals[$status]++;
                }
                @endphp
                <td class="col-day {{ $status ? 'status-'.$status : '' }}">{{ $status }}</td>
                @endforeach

            </tr>
            @endforeach

            {{-- Summary row --}}
            <tr class="summary-row">
                <td colspan="2" style="text-align: right; padding-right: 6px;"><strong>TOTAL DIÁRIO</strong></td>
                @foreach($days as $day)
                @php
                $dateKey = $day->format('Y-m-d');
                $dayCount = 0;
                foreach ($attendanceMap as $sid => $records) {
                if (isset($records[$dateKey]) && $records[$dateKey] === 'P') {
                $dayCount++;
                }
                }
                @endphp
                <td>{{ $dayCount ?: '' }}</td>
                @endforeach

            </tr>
        </tbody>
    </table>

    <div class="legend">
        <span><span class="lbl status-P">P</span> = Presente</span>
        <span><span class="lbl status-F">F</span> = Falta</span>
        <span><span class="lbl status-J">J</span> = Falta Justificada</span>
        <span><span class="lbl status-A">A</span> = Atraso</span>
    </div>
    @else
    <p style="text-align: center; padding: 40px; color: #999; font-size: 12px;">Sem registos de presenças encontrados para o período seleccionado.</p>
    @endif

    <div class="footer">
        <span>SIGEF — {{ now()->format('d/m/Y H:i') }} | Página <span class="page-number"></span></span>
    </div>
</body>

</html>
