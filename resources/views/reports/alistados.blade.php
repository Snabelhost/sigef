@extends('reports.layout')

@section('title', 'ALISTADOS')
@section('subtitle', 'Lista de candidatos alistados')

@section('filters')
    @if($academicYear) <strong>Ano Lectivo:</strong> {{ $academicYear->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $academicYear && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">Nº</th>
                    <th class="text-left" style="width: 30%;">Nome</th>
                    <th style="width: 13%;">Nº BI</th>
                    <th style="width: 8%;">Sexo</th>
                    <th class="text-left" style="width: 23%;">Proveniencia</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 11%;">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->full_name }}</td>
                        <td class="text-center">{{ $record->id_number ?? '-' }}</td>
                        <td class="text-center">{{ $record->gender ?? '-' }}</td>
                        <td>{{ $record->provenance?->name ?? '-' }}</td>
                        <td class="text-center">{{ ucfirst($record->status ?? '-') }}</td>
                        <td class="text-center">{{ $record->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
