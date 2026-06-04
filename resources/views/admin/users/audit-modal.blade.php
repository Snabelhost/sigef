@php
    $total = $activities->count();
@endphp

<div class="sigef-user-audit-modal">
    <style>
        .sigef-user-audit-modal {
            color: #0f172a;
            font-size: 13px;
        }

        .sigef-user-audit-summary {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 12px 14px;
        }

        .sigef-user-audit-summary strong {
            color: #001b4d;
            display: block;
            font-size: 14px;
        }

        .sigef-user-audit-summary span {
            color: #64748b;
            display: block;
            font-size: 12px;
            margin-top: 2px;
        }

        .sigef-user-audit-count {
            background: #001f5f;
            border-radius: 999px;
            color: #fff;
            font-weight: 800;
            padding: 6px 12px;
            white-space: nowrap;
        }

        .sigef-user-audit-table-wrap {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            max-height: 520px;
            overflow: auto;
        }

        .sigef-user-audit-table {
            border-collapse: collapse;
            min-width: 980px;
            width: 100%;
        }

        .sigef-user-audit-table th,
        .sigef-user-audit-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 10px;
            text-align: left;
            vertical-align: top;
        }

        .sigef-user-audit-table th {
            background: #041842;
            color: #fff;
            font-size: 12px;
            font-weight: 850;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sigef-user-audit-table td {
            background: #fff;
        }

        .sigef-user-audit-event {
            color: #001b4d;
            font-weight: 850;
        }

        .sigef-user-audit-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            padding: 18px;
            text-align: center;
        }
    </style>

    <div class="sigef-user-audit-summary">
        <div>
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->email }} | {{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
        </div>
        <div class="sigef-user-audit-count">{{ $total }} registos</div>
    </div>

    @if ($activities->isEmpty())
        <div class="sigef-user-audit-empty">
            Ainda nao existem actividades registadas para este utilizador.
        </div>
    @else
        <div class="sigef-user-audit-table-wrap">
            <table class="sigef-user-audit-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Evento</th>
                        <th>Descricao</th>
                        <th>Actor</th>
                        <th>Metodo</th>
                        <th>Rota</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>{{ optional($activity->created_at)->format('d/m/Y H:i:s') ?: '-' }}</td>
                            <td class="sigef-user-audit-event">{{ $activity->event_label }}</td>
                            <td>{{ $activity->description ?: '-' }}</td>
                            <td>{{ $activity->actor?->name ?: 'Sistema' }}</td>
                            <td>{{ $activity->method ?: '-' }}</td>
                            <td>{{ parse_url((string) $activity->path, PHP_URL_PATH) ?: ($activity->path ?: '-') }}</td>
                            <td>{{ $activity->ip_address ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
