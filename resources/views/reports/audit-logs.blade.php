@extends('reports.layout')

@section('title', 'Relatório de Auditoria')

@section('filters')
    @if($selectedUser) <strong>Utilizador:</strong> {{ $selectedUser->name }} @endif
    @if($event) | <strong>Evento:</strong> {{ \App\Support\AuditReportFormatter::auditEventLabel($event) }} @endif
    @if($modelLabel) | <strong>Módulo:</strong> {{ $modelLabel }} @endif
    @if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection

@section('summary')
    <span>Total: {{ $summary['total'] }}</span>
    <span style="margin-left: 18px;">Criados: {{ $summary['created'] }}</span>
    <span style="margin-left: 18px;">Atualizados: {{ $summary['updated'] }}</span>
    <span style="margin-left: 18px;">Eliminados: {{ $summary['deleted'] }}</span>
    <span style="margin-left: 18px;">Utilizadores: {{ $summary['users'] }}</span>
@endsection

@section('content')
    <style>
        .report-meta-note {
            color: #64748b;
            font-size: 9px;
            margin: -4px 0 12px;
        }

        .col-date { width: 105px; }
        .col-event { width: 120px; }
        .col-module { width: 110px; }
        .col-user { width: 140px; }
        .col-ip { width: 88px; }
        .audit-description {
            line-height: 1.35;
        }
    </style>

    <p class="report-meta-note">
        Documento gerado a partir dos registos de auditoria de criação, atualização, eliminação e restauração de dados do SIGEF.
    </p>

    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th class="col-date">Data/Hora</th>
                    <th class="col-event">Evento</th>
                    <th class="col-module">Módulo</th>
                    <th class="col-user">Utilizador</th>
                    <th>Descrição</th>
                    <th class="col-ip">IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    @php
                        $eventName = (string) $record->event;
                        $badgeClass = match ($eventName) {
                            'created' => 'badge-success',
                            'updated' => 'badge-warning',
                            'deleted' => 'badge-danger',
                            'restored' => 'badge-info',
                            default => 'badge-gray',
                        };
                    @endphp
                    <tr>
                        <td>{{ $record->created_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ \App\Support\AuditReportFormatter::auditEventLabel($record) }}</span></td>
                        <td>{{ \App\Support\AuditReportFormatter::auditModelLabel($record->auditable_type) }}</td>
                        <td>{{ $record->user?->name ?? 'Sistema' }}</td>
                        <td class="audit-description">{{ \App\Support\AuditReportFormatter::auditDescription($record) }}</td>
                        <td>{{ $record->ip_address ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos de auditoria encontrados para os filtros selecionados.</p>
    @endif
@endsection
