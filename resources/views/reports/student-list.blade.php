<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Lista de Formandos</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
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
            font-size: 18px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #888;
        }
        .total {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR</h2>
        <h2>POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>{{ $institution->name }}</h2>
        <p class="subtitle">Lista Geral de Formandos em Formação</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60px;">Nº Ordem</th>
                <th>Nome Completo</th>
                <th style="width: 100px;">BI</th>
                <th style="width: 60px;">CIA</th>
                <th style="width: 70px;">Pelotão</th>
                <th style="width: 80px;">Data Matr.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td>{{ $student->student_number }}</td>
                <td>{{ $student->candidate->full_name }}</td>
                <td>{{ $student->candidate->id_number }}</td>
                <td>{{ $student->cia ?? '-' }}</td>
                <td>{{ $student->platoon ?? '-' }}</td>
                <td>{{ $student->enrollment_date?->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total de Formandos: {{ $students->count() }}</p>

    <div class="footer">
        <p>Documento gerado em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SIGEF - Sistema Integrado de Gestão da Escola de Formação</p>
    </div>
</body>
</html>
