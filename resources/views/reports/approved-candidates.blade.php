<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Candidatos Aprovados</title>
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
        .header .subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 10px;
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
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #888;
        }
        .total {
            margin-top: 15px;
            font-weight: bold;
            font-size: 11px;
        }
        .approved-badge {
            background-color: #10b981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR</h2>
        <h2>POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>DIRECÇÃO DE PESSOAL E QUADROS</h2>
        <p class="subtitle">Lista de Candidatos Aprovados para Admissão</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">Nº</th>
                <th>Nome Completo</th>
                <th style="width: 90px;">Nº BI</th>
                <th style="width: 50px;">Género</th>
                <th style="width: 80px;">Nascimento</th>
                <th style="width: 90px;">Província</th>
                <th style="width: 100px;">Recrutamento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($candidates as $index => $candidate)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $candidate->full_name }}</td>
                <td>{{ $candidate->id_number }}</td>
                <td>{{ $candidate->gender == 'M' ? 'Masculino' : 'Feminino' }}</td>
                <td>{{ $candidate->birth_date?->format('d/m/Y') }}</td>
                <td>{{ $candidate->provenance->name ?? '-' }}</td>
                <td>{{ $candidate->recruitmentType->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total de Candidatos Aprovados: {{ $candidates->count() }}</p>

    <div class="footer">
        <p>Documento gerado em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SIGEF - Sistema Integrado de Gestão da Escola de Formação</p>
    </div>
</body>
</html>
