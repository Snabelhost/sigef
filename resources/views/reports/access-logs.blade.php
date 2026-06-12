@extends('reports.layout')

@section('title', 'REGISTO PRIMARIO DE ACESSO')
@section('subtitle', 'Utilizadores e ultimo acesso ao sistema')

@section('filters')
    @if($selectedUser) <strong>Utilizador:</strong> {{ $selectedUser->name }} @endif
    @if($action) @if($selectedUser) | @endif <strong>Evento:</strong> {{ \App\Support\AuditReportFormatter::activityActionLabel($action) }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $selectedUser && ! $action && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os utilizadores @endif
@endsection

@section('summary')
    Total: {{ $summary['total'] }} |
    Activos: {{ $summary['active'] }} |
    Inactivos: {{ $summary['inactive'] }} |
    Com acesso registado: {{ $summary['with_access'] }}
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width: 28%;">Nome</th>
                    <th class="text-left" style="width: 30%;">Email</th>
                    <th class="text-left" style="width: 18%;">Perfil</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 14%;">Ultimo acesso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        <td>{{ $record['name'] }}</td>
                        <td>{{ $record['email'] }}</td>
                        <td>{{ $record['profile'] }}</td>
                        <td class="text-center">{{ $record['state'] }}</td>
                        <td class="text-center">{{ $record['last_access'] ? \Carbon\Carbon::parse($record['last_access'])->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos de acesso encontrados para os filtros selecionados.</p>
    @endif
@endsection
