<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Mapa de Faltas e Dispensas</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 12px;
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background-color: #f59e0b;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #fef3c7;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #888;
        }
        .summary {
            margin-top: 15px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR - POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>{{ $institution->name }}</h2>
        <p>Mapa de Faltas e Dispensas</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">Nº</th>
                <th style="width: 60px;">Nº Ordem</th>
                <th>Nome do Formando</th>
                <th style="width: 80px;">Tipo</th>
                <th style="width: 70px;">Início</th>
                <th style="width: 70px;">Fim</th>
                <th style="width: 50px;">Dias</th>
                <th style="width: 60px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leaves as $index => $leave)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $leave->student->student_number }}</td>
                <td>{{ $leave->student->candidate->full_name }}</td>
                <td>
                    @switch($leave->leave_type)
                        @case('saude') Saúde @break
                        @case('pessoal') Pessoal @break
                        @case('servico') Serviço @break
                        @case('falecimento') Falecimento @break
                        @default {{ ucfirst($leave->leave_type) }}
                    @endswitch
                </td>
                <td>{{ $leave->start_date?->format('d/m/Y') }}</td>
                <td>{{ $leave->end_date?->format('d/m/Y') }}</td>
                <td>{{ $leave->start_date && $leave->end_date ? $leave->start_date->diffInDays($leave->end_date) + 1 : '-' }}</td>
                <td>
                    <span class="status-{{ $leave->status }}">
                        @switch($leave->status)
                            @case('pending') Pendente @break
                            @case('approved') Aprovada @break
                            @case('rejected') Rejeitada @break
                        @endswitch
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total de Registos:</strong> {{ $leaves->count() }}</p>
        <p><strong>Aprovadas:</strong> {{ $leaves->where('status', 'approved')->count() }} | 
           <strong>Pendentes:</strong> {{ $leaves->where('status', 'pending')->count() }} |
           <strong>Rejeitadas:</strong> {{ $leaves->where('status', 'rejected')->count() }}</p>
    </div>

    <div class="footer">
        <p>Documento gerado em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SIGEF - Sistema Integrado de Gestão da Escola de Formação</p>
    </div>
</body>
</html>
