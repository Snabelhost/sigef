@extends('reports.layout')

@section('title', strtoupper($reportLabel))
@section('subtitle', 'Lista de formandos por filtros seleccionados')

@section('filters')
    <strong>Tipo:</strong> {{ $studentType }}
    @if($institution) | <strong>Escola:</strong> {{ $institution->name }} @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
    @if($academicYear) | <strong>Ano Lectivo:</strong> {{ $academicYear->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">Nº</th>
                    <th class="text-left" style="width: 25%;">Nome</th>
                    <th style="width: 10%;">NIP/NURI</th>
                    <th style="width: 11%;">BI</th>
                    <th class="text-left" style="width: 18%;">Curso</th>
                    <th style="width: 8%;">Turma</th>
                    <th style="width: 6%;">CIA</th>
                    <th style="width: 7%;">Pelotao</th>
                    <th style="width: 7%;">Seccao</th>
                    <th style="width: 4%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['name'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['number'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['bi'] ?: '-' }}</td>
                        <td>{{ $record['course'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['class'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['cia'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['platoon'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['section'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['type'] ?: $studentType }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
