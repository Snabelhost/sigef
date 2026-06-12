@extends('reports.layout')

@section('title', 'USUARIOS POR PERFIL')
@section('subtitle', 'Utilizadores agrupados por perfil de acesso. Total de atribuicoes: '.$summary['assignments'].' | Total de usuarios unicos: '.$summary['unique'])

@section('filters')
    @if($dateFrom || $dateTo)
        <strong>Periodo:</strong> {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Inicio' }} a {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Hoje' }}
    @else
        <strong>Periodo:</strong> Todos os registos
    @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th class="text-left">Perfil</th>
                    <th style="width: 16%;">Total</th>
                    <th style="width: 16%;">Activos</th>
                    <th style="width: 16%;">Inactivos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        <td>{{ $record['profile'] }}</td>
                        <td class="text-center">{{ $record['total'] }}</td>
                        <td class="text-center">{{ $record['active'] }}</td>
                        <td class="text-center">{{ $record['inactive'] }}</td>
                    </tr>
                @endforeach
                <tr class="group-row">
                    <td>TOTAL GERAL</td>
                    <td class="text-center">{{ $summary['unique'] }}</td>
                    <td class="text-center">{{ $summary['active'] }}</td>
                    <td class="text-center">{{ $summary['inactive'] }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
