<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Guia de Marcha</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 13px;
            font-weight: normal;
        }
        .document-title {
            text-align: center;
            margin: 30px 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .content {
            line-height: 1.8;
            text-align: justify;
            margin: 20px 0;
        }
        .info-table {
            width: 100%;
            margin: 20px 0;
        }
        .info-table td {
            padding: 8px 0;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
            width: 180px;
        }
        .signature-area {
            margin-top: 80px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin: 0 auto;
            padding-top: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .stamp-area {
            margin-top: 30px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR</h2>
        <h2>POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>{{ $student->institution->name ?? 'Escola de Formação' }}</h2>
    </div>

    <div class="document-title">
        GUIA DE MARCHA
    </div>

    <div class="content">
        <p>O Director da {{ $student->institution->name ?? 'Escola de Formação' }} manda apresentar à entidade competente, 
        para os devidos efeitos, o(a) elemento abaixo identificado(a):</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nome Completo:</td>
            <td>{{ $student->candidate->full_name }}</td>
        </tr>
        <tr>
            <td class="label">Nº do BI:</td>
            <td>{{ $student->candidate->id_number }}</td>
        </tr>
        <tr>
            <td class="label">Nº de Ordem:</td>
            <td>{{ $student->student_number }}</td>
        </tr>
        <tr>
            <td class="label">NURI:</td>
            <td>{{ $student->nuri ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Companhia:</td>
            <td>{{ $student->cia ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Pelotão:</td>
            <td>{{ $student->platoon ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Secção:</td>
            <td>{{ $student->section ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Data de Matrícula:</td>
            <td>{{ $student->enrollment_date?->format('d/m/Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Estado Actual:</td>
            <td>{{ ucfirst($student->status) }}</td>
        </tr>
    </table>

    <div class="content">
        <p><strong>Motivo:</strong> _______________________________________________________________</p>
        <p><strong>Destino:</strong> _______________________________________________________________</p>
        <p><strong>Observações:</strong> ___________________________________________________________</p>
    </div>

    <div class="stamp-area">
        <p><strong>Data:</strong> {{ $generatedAt->format('d') }} de {{ $generatedAt->translatedFormat('F') }} de {{ $generatedAt->format('Y') }}</p>
    </div>

    <div class="signature-area">
        <div class="signature-line">
            O Director
        </div>
    </div>

    <div class="footer">
        <p>Documento gerado pelo SIGEF em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>Sistema Integrado de Gestão da Escola de Formação</p>
    </div>
</body>
</html>
