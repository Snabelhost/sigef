<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pauta de Notas</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 11px;
            font-weight: normal;
        }
        .class-info {
            margin: 10px 0;
            padding: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #999;
            padding: 4px 5px;
            text-align: center;
        }
        th {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
            font-size: 8px;
        }
        th.student-name {
            text-align: left;
            width: 150px;
        }
        td.student-name {
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .grade-negative {
            color: #dc2626;
            font-weight: bold;
        }
        .grade-positive {
            color: #059669;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 8px;
            color: #888;
        }
        .summary {
            margin-top: 15px;
            font-size: 10px;
        }
        .average {
            font-weight: bold;
            background-color: #fef3c7;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPÚBLICA DE ANGOLA</h1>
        <h2>MINISTÉRIO DO INTERIOR - POLÍCIA NACIONAL ANGOLANA</h2>
        <h2>PAUTA DE NOTAS</h2>
    </div>

    <div class="class-info">
        <strong>Turma:</strong> {{ $class->name ?? 'N/A' }} | 
        <strong>Ano Académico:</strong> {{ $class->academicYear->year ?? 'N/A' }} |
        <strong>Escola:</strong> {{ $class->institution->name ?? 'N/A' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">Nº</th>
                <th class="student-name">Nome do Formando</th>
                @php
                    $subjects = collect();
                    foreach($students as $student) {
                        foreach($student->evaluations as $eval) {
                            if(!$subjects->contains('id', $eval->subject->id)) {
                                $subjects->push($eval->subject);
                            }
                        }
                    }
                @endphp
                @foreach($subjects as $subject)
                <th style="width: 50px;">{{ Str::limit($subject->name, 10) }}</th>
                @endforeach
                <th style="width: 50px;">Média</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $student->student_number }}</td>
                <td class="student-name">{{ $student->candidate->full_name }}</td>
                @php
                    $totalScore = 0;
                    $evalCount = 0;
                @endphp
                @foreach($subjects as $subject)
                    @php
                        $eval = $student->evaluations->where('subject_id', $subject->id)->first();
                        $score = $eval ? $eval->score : null;
                        if($score !== null) {
                            $totalScore += $score;
                            $evalCount++;
                        }
                    @endphp
                    <td class="{{ $score !== null && $score < 10 ? 'grade-negative' : 'grade-positive' }}">
                        {{ $score !== null ? number_format($score, 1) : '-' }}
                    </td>
                @endforeach
                <td class="average">
                    {{ $evalCount > 0 ? number_format($totalScore / $evalCount, 1) : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total de Formandos:</strong> {{ $students->count() }}</p>
    </div>

    <div class="footer">
        <p>Documento gerado em: {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SIGEF - Sistema Integrado de Gestão da Escola de Formação</p>
    </div>
</body>
</html>
