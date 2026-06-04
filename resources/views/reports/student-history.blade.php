<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico do Formando</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 12px;
            font-weight: normal;
        }
        .document-title {
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .section {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section-title {
            font-weight: bold;
            font-size: 12px;
            color: #1e40af;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 3px 0;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
        }
        .grade-negative {
            color: #dc2626;
            font-weight: bold;
        }
        .grade-positive {
            color: #059669;
        }
        .photo-placeholder {
            width: 80px;
            height: 100px;
            border: 1px solid #ccc;
            float: right;
            text-align: center;
            line-height: 100px;
            font-size: 8px;
            color: #999;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .average-box {
            background-color: #fef3c7;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }
        .average-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR - POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>{{ $student->institution->name ?? 'Escola de Formação' }}</h2>
    </div>

    <div class="document-title">
        HISTÓRICO ACADÉMICO DO FORMANDO
    </div>

    <div class="section">
        <div class="section-title">DADOS PESSOAIS</div>
        <div class="photo-placeholder">
            @if($student->candidate->photo)
                FOTO
            @else
                SEM FOTO
            @endif
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Nome Completo:</span>
                <span class="info-value">{{ $student->candidate->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nº do BI:</span>
                <span class="info-value">{{ $student->candidate->id_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Nascimento:</span>
                <span class="info-value">{{ $student->candidate->birth_date?->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Género:</span>
                <span class="info-value">{{ $student->candidate->gender }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Província de Origem:</span>
                <span class="info-value">{{ $student->candidate->provenance->name ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">DADOS ACADÉMICOS</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Nº de Ordem:</span>
                <span class="info-value">{{ $student->student_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">NURI:</span>
                <span class="info-value">{{ $student->nuri ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tipo de Formando:</span>
                <span class="info-value">{{ ucfirst($student->student_type) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Matrícula:</span>
                <span class="info-value">{{ $student->enrollment_date?->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Companhia/Pelotão/Secção:</span>
                <span class="info-value">{{ $student->cia ?? '-' }} / {{ $student->platoon ?? '-' }} / {{ $student->section ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado Actual:</span>
                <span class="info-value">{{ ucfirst($student->status) }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">HISTÓRICO DE AVALIAÇÕES</div>
        @if($student->evaluations->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th style="width: 80px;">Tipo</th>
                    <th style="width: 50px;">Nota</th>
                    <th style="width: 80px;">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student->evaluations as $eval)
                <tr>
                    <td>{{ $eval->subject->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($eval->evaluation_type) }}</td>
                    <td class="{{ $eval->score < 10 ? 'grade-negative' : 'grade-positive' }}">
                        {{ number_format($eval->score, 1) }}
                    </td>
                    <td>{{ $eval->evaluated_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="average-box">
            <p>Média Geral</p>
            <div class="average-value">
                {{ number_format($student->evaluations->avg('score'), 2) }}
            </div>
            <p>em {{ $student->evaluations->count() }} avaliações</p>
        </div>
        @else
        <p style="color: #888;">Nenhuma avaliação registada.</p>
        @endif
    </div>

    @if($student->leaves && $student->leaves->count() > 0)
    <div class="section">
        <div class="section-title">HISTÓRICO DE FALTAS/DISPENSAS</div>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th style="width: 70px;">Início</th>
                    <th style="width: 70px;">Fim</th>
                    <th style="width: 50px;">Dias</th>
                    <th style="width: 60px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student->leaves as $leave)
                <tr>
                    <td>{{ ucfirst($leave->leave_type) }}</td>
                    <td>{{ $leave->start_date?->format('d/m/Y') }}</td>
                    <td>{{ $leave->end_date?->format('d/m/Y') }}</td>
                    <td>{{ $leave->start_date && $leave->end_date ? $leave->start_date->diffInDays($leave->end_date) + 1 : '-' }}</td>
                    <td>{{ ucfirst($leave->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Documento gerado em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SIGEF - Sistema Integrado de Gestão da Escola de Formação</p>
        <p>Este documento é válido sem assinatura quando gerado pelo sistema.</p>
    </div>
</body>
</html>
