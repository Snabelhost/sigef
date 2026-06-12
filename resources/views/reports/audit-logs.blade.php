@extends('reports.layout')

@section('title', 'AUDITORIA')
@section('subtitle', 'Actividades registadas no painel administrativo')

@section('filters')
    @if($selectedUser) <strong>Utilizador:</strong> {{ $selectedUser->name }} @endif
    @if($event) | <strong>Evento:</strong> {{ \App\Support\AuditReportFormatter::auditEventLabel($event) }} @endif
    @if($modelLabel) | <strong>Modulo:</strong> {{ $modelLabel }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $selectedUser && ! $event && ! $modelLabel && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('summary')
    Total: {{ $summary['total'] }} |
    Criados: {{ $summary['created'] }} |
    Atualizados: {{ $summary['updated'] }} |
    Eliminados: {{ $summary['deleted'] }} |
    Utilizadores: {{ $summary['users'] }}
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 10%;">Data/Hora</th>
                    <th class="text-left" style="width: 16%;">Utilizador</th>
                    <th style="width: 12%;">Evento</th>
                    <th class="text-left" style="width: 31%;">Descricao</th>
                    <th style="width: 9%;">IP</th>
                    <th class="text-left" style="width: 17%;">Rota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        <td class="text-center">{{ $record->id }}</td>
                        <td class="text-center">{{ $record->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $record->user?->name ?? 'Sistema' }}</td>
                        <td class="text-center">{{ \App\Support\AuditReportFormatter::auditEventLabel($record) }}</td>
                        <td>{{ \App\Support\AuditReportFormatter::auditDescription($record) }}</td>
                        <td class="text-center">{{ $record->ip_address ?? '-' }}</td>
                        <td>{{ trim(($record->request_method ?? '').' '.($record->url ?? $record->route ?? '-')) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos de auditoria encontrados para os filtros selecionados.</p>
    @endif
@endsection
