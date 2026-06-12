@extends('reports.layout')

@section('title', 'MAPA DOS DOCENTES PARA O ANO ACADEMICO')
@section('subtitle', 'Investigacao, competencia e profissionalismo')

@section('filters')
    @if($institution)
        <strong>Escola:</strong> {{ $institution->name }}
    @else
        <strong>Escola:</strong> Todas
    @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">Nº</th>
                    <th style="width: 11%;">Patente</th>
                    <th class="text-left" style="width: 24%;">Nome completo</th>
                    <th style="width: 16%;">Grau academico / Especializacao</th>
                    <th style="width: 10%;">Situacao</th>
                    <th class="text-left" style="width: 25%;">Disc. que lecciona</th>
                    <th style="width: 10%;">Ano curricular</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    @php
                        $trainer = $record->trainer;
                        $subject = $record->subject;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $trainer?->rank?->name ?? '-' }}</td>
                        <td>{{ $trainer?->full_name ?? '-' }}</td>
                        <td class="text-center">{{ trim(($trainer?->education_level ?: '-').' / '.($trainer?->specialization ?: '-')) }}</td>
                        <td class="text-center">{{ $trainer?->situation ?: $trainer?->trainer_type ?: '-' }}</td>
                        <td>{{ $subject?->name ?? '-' }}</td>
                        <td class="text-center">{{ $subject?->coursePhase?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="report-signatures">
            <div>Departamento/Area, em Luanda, ______/______/{{ now()->format('Y') }}.</div>
            <div class="signature-line">O CHEFE DE DEPARTAMENTO</div>
        </div>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
