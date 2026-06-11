@extends('reports.layout')

@section('title', 'Relatório de Acessos')

@section('filters')
    @if($selectedUser) <strong>Utilizador:</strong> {{ $selectedUser->name }} @endif
    @if($action) | <strong>Evento:</strong> {{ \App\Support\AuditReportFormatter::activityActionLabel($action) }} @endif
    @if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection

@section('summary')
    <span>Total: {{ $summary['total'] }}</span>
    <span style="margin-left: 18px;">Entradas: {{ $summary['logins'] }}</span>
    <span style="margin-left: 18px;">Saídas: {{ $summary['logouts'] }}</span>
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
        .col-event { width: 105px; }
        .col-user { width: 150px; }
        .col-device { width: 120px; }
        .col-ip { width: 88px; }
        .col-method { width: 55px; text-align: center; }
    </style>

    <p class="report-meta-note">
        Documento gerado a partir dos registos de autenticação e encerramento de sessão guardados pelo SIGEF.
    </p>

    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th class="col-date">Data/Hora</th>
                    <th class="col-event">Evento</th>
                    <th class="col-user">Utilizador</th>
                    <th>E-mail</th>
                    <th class="col-ip">IP</th>
                    <th class="col-device">Dispositivo</th>
                    <th>Navegador</th>
                    <th>Plataforma</th>
                    <th class="col-method">Método</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    @php
                        $isLogin = $record->action === 'login';
                        $badgeClass = $isLogin ? 'badge-success' : 'badge-gray';
                    @endphp
                    <tr>
                        <td>{{ $record->created_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ \App\Support\AuditReportFormatter::activityActionLabel($record) }}</span></td>
                        <td>{{ $record->user?->name ?? 'Utilizador removido' }}</td>
                        <td>{{ $record->user?->email ?? '-' }}</td>
                        <td>{{ $record->ip_address ?? '-' }}</td>
                        <td>{{ ucfirst($record->device_type ?? '-') }}</td>
                        <td>{{ $record->browser ?? '-' }}</td>
                        <td>{{ $record->platform ?? '-' }}</td>
                        <td class="text-center">{{ $record->method ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos de acesso encontrados para os filtros selecionados.</p>
    @endif
@endsection
