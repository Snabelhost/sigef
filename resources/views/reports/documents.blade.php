@extends('reports.layout')

@section('title', 'DOCUMENTOS ADMINISTRATIVOS')
@section('subtitle', 'Documentos registados e tramitados no sistema')

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $institution && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">N</th>
                    <th class="text-left" style="width: 28%;">Titulo</th>
                    <th style="width: 14%;">Referencia</th>
                    <th class="text-left" style="width: 20%;">Origem</th>
                    <th style="width: 12%;">Prioridade</th>
                    <th style="width: 12%;">Estado</th>
                    <th style="width: 10%;">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['title'] }}</td>
                        <td class="text-center">{{ $record['reference'] }}</td>
                        <td>{{ $record['origin'] }}</td>
                        <td class="text-center">{{ $record['priority'] }}</td>
                        <td class="text-center">{{ $record['status'] }}</td>
                        <td class="text-center">{{ $record['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
