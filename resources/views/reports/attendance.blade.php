@extends('reports.layout')

@section('title', 'PONTO DE PRESENCAS')
@section('subtitle', 'Mapa de presencas dos formandos por dia')

@push('styles')
    <style>
        .presence-table th,
        .presence-table td {
            font-size: 6.1px;
            padding: 2px 2px;
            text-align: center;
        }

        .presence-table .presence-name {
            text-align: left;
            width: 22%;
        }

        .presence-table .presence-num {
            width: 4%;
        }

        .status-P { color: #166534; font-weight: 800; }
        .status-F { color: #991b1b; font-weight: 800; }
        .status-J { color: #854d0e; font-weight: 800; }
        .status-A { color: #1e40af; font-weight: 800; }

        .presence-legend {
            font-size: 7px;
            font-weight: 700;
            margin-top: 6px;
        }

        .presence-legend span {
            margin-right: 12px;
        }
    </style>
@endpush

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($cia) | <strong>CIA:</strong> {{ $cia }} @endif
    | <strong>Periodo:</strong> {{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}
    | <strong>Dias:</strong> {{ $totalDays }}
@endsection

@section('content')
    @if($students->count())
        <table class="presence-table">
            <thead>
                <tr>
                    <th class="presence-num">N</th>
                    <th class="presence-name">Nome do Formando</th>
                    @foreach($days as $day)
                        <th>{{ $day->format('d') }}<br>{{ $dayNames[$day->format('N')] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($students as $index => $student)
                    @php
                        $attendances = $attendanceMap[$student->id] ?? [];
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="presence-name">{{ $student->candidate?->full_name ?? $student->full_name ?? '-' }}</td>
                        @foreach($days as $day)
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $status = $attendances[$dateKey] ?? '';
                            @endphp
                            <td class="{{ $status ? 'status-'.$status : '' }}">{{ $status }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" class="text-right text-bold">TOTAL DIARIO</td>
                    @foreach($days as $day)
                        @php
                            $dateKey = $day->format('Y-m-d');
                            $dayCount = 0;

                            foreach ($attendanceMap as $records) {
                                if (($records[$dateKey] ?? null) === 'P') {
                                    $dayCount++;
                                }
                            }
                        @endphp
                        <td class="text-bold">{{ $dayCount ?: '' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <div class="presence-legend">
            <span><span class="status-P">P</span> = Presente</span>
            <span><span class="status-F">F</span> = Falta</span>
            <span><span class="status-J">J</span> = Falta Justificada</span>
            <span><span class="status-A">A</span> = Atraso</span>
        </div>
    @else
        <p class="no-data">Sem registos de presencas encontrados para o periodo seleccionado.</p>
    @endif
@endsection
