<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscrição - {{ $student->candidate?->full_name ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #041B4E;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #041B4E;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        
        .header .institution {
            font-size: 14px;
            color: #041B4E;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .section-header {
            background: #041B4E;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .section-body {
            padding: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .info-item {
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .info-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 13px;
            color: #333;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-primary {
            background: #041B4E;
            color: white;
        }
        
        .badge-success {
            background: #0d5442;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
        }
        
        table th {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        .print-date {
            text-align: right;
            font-size: 10px;
            color: #999;
            margin-top: 30px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            .container {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #041B4E; color: white; padding: 10px; text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: white; color: #041B4E; border: none; padding: 10px 30px; border-radius: 5px; cursor: pointer; font-weight: bold;">
            🖨️ Imprimir Ficha
        </button>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>SIGEF - Sistema de Gestão de Formação</h1>
            <h2>Ficha de Inscrição do Formando</h2>
            <div class="institution">{{ $student->institution?->name ?? 'Instituição não definida' }}</div>
        </div>
        
        {{-- Dados Pessoais --}}
        <div class="section">
            <div class="section-header">Dados Pessoais</div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nº de Ordem</div>
                        <div class="info-value">{{ $student->student_number ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value">{{ $student->candidate?->full_name ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nº do BI</div>
                        <div class="info-value">{{ $student->candidate?->bi_number ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Telefone</div>
                        <div class="info-value">{{ $student->candidate?->phone ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <span class="badge badge-primary">{{ $student->student_type ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data de Inscrição</div>
                        <div class="info-value">{{ $student->enrollment_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Localização --}}
        <div class="section">
            <div class="section-header">Localização</div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">CIA</div>
                        <div class="info-value">{{ $student->cia ? $student->cia . 'ª CIA' : '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pelotão</div>
                        <div class="info-value">{{ $student->platoon ? $student->platoon . 'º PELOTÃO' : '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Secção</div>
                        <div class="info-value">{{ $student->section ? $student->section . 'ª SECÇÃO' : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Curso e Turma --}}
        <div class="section">
            <div class="section-header">Curso e Turma</div>
            <div class="section-body">
                @php
                    $lastEnrollment = $student->classEnrollments->first();
                    $courseName = $lastEnrollment?->studentClass?->courseMap?->course?->name ?? '-';
                    $className = $lastEnrollment?->studentClass?->name ?? '-';
                    $phaseName = $lastEnrollment?->coursePhase?->name ?? '-';
                @endphp
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Curso</div>
                        <div class="info-value">{{ $courseName }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Turma</div>
                        <div class="info-value">{{ $className }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fase</div>
                        <div class="info-value">{{ $phaseName }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Disciplinas --}}
        <div class="section">
            <div class="section-header">Disciplinas Inscritas ({{ $student->subjectEnrollments->count() }})</div>
            <div class="section-body">
                @if($student->subjectEnrollments->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Disciplina</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student->subjectEnrollments as $index => $enrollment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $enrollment->subject?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $enrollment->is_active ? 'badge-success' : 'badge-primary' }}">
                                            {{ $enrollment->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: #666; font-style: italic;">Nenhuma disciplina inscrita.</p>
                @endif
            </div>
        </div>
        
        {{-- Assinatura --}}
        <div class="footer">
            <div class="signature-area">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Assinatura do Formando</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Assinatura do Responsável</div>
                </div>
            </div>
            
            <div class="print-date">
                Documento gerado em: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
