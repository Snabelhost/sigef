<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ficha de Inscrição</title>
    <style>
        @page {
            margin: 10mm 20mm 10mm 20mm;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        
        .header img {
            height: 80px;
        }
        
        .institution {
            font-size: 11pt;
            color: #041B4E;
            font-weight: bold;
            margin: 3px 0;
        }
        
        .title {
            font-size: 14pt;
            color: #041B4E;
            font-weight: bold;
            margin: 5px 0 0 0;
        }
        
        .field {
            margin-bottom: 8px;
        }
        
        .field-label {
            font-size: 8pt;
            color: #666;
        }
        
        .field-line {
            border-bottom: 1px solid #aaa;
            min-height: 18px;
            font-size: 10pt;
            padding-top: 2px;
        }
        
        .row {
            width: 100%;
            margin-bottom: 8px;
        }
        
        .row:after {
            content: "";
            display: table;
            clear: both;
        }
        
        .col-half {
            width: 48%;
            float: left;
        }
        
        .col-half:first-child {
            margin-right: 4%;
        }
        
        .col-third {
            width: 30%;
            float: left;
            margin-right: 5%;
        }
        
        .col-third:last-child {
            margin-right: 0;
        }
        
        .section-title {
            font-size: 10pt;
            color: #041B4E;
            font-weight: bold;
            margin: 12px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #041B4E;
        }
        
        .badge {
            background: #041B4E;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9pt;
        }
        
        .disc-container {
            margin-top: 6px;
        }
        
        .disc-col {
            width: 48%;
            float: left;
        }
        
        .disc-col:first-child {
            margin-right: 4%;
        }
        
        .disc-item {
            padding: 3px 0;
            border-bottom: 1px dotted #ddd;
            font-size: 9pt;
        }
        
        .disc-num {
            display: inline-block;
            width: 20px;
            color: #666;
        }
        
        .signatures {
            margin-top: 25px;
        }
        
        .sig-box {
            width: 45%;
            float: left;
            text-align: center;
        }
        
        .sig-box:last-child {
            float: right;
        }
        
        .sig-line {
            border-top: 1px solid #333;
            margin-top: 35px;
            margin-bottom: 4px;
        }
        
        .sig-label {
            font-size: 8pt;
            color: #666;
        }
        
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 8pt;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo-pna.png') }}" alt="Logo">
        <div class="institution">{{ $student->institution?->name ?? 'Polícia Nacional de Angola' }}</div>
        <div class="title">Ficha de Inscrição</div>
    </div>
    
    <div class="field">
        <div class="field-label">Nome Completo</div>
        <div class="field-line">{{ $student->candidate?->full_name ?? '' }}</div>
    </div>
    
    <div class="row">
        <div class="col-half">
            <div class="field-label">Nº de Ordem</div>
            <div class="field-line">{{ $student->student_number ?? '' }}</div>
        </div>
        <div class="col-half">
            <div class="field-label">Nº do BI</div>
            <div class="field-line">{{ $student->candidate?->id_number ?? '' }}</div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-half">
            <div class="field-label">Telefone</div>
            <div class="field-line">{{ $student->candidate?->phone ?? '' }}</div>
        </div>
        <div class="col-half">
            <div class="field-label">Data de Inscrição</div>
            <div class="field-line">{{ $student->enrollment_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-half">
            <div class="field-label">Estado</div>
            <div class="field-line"><span class="badge">{{ $student->student_type ?? '-' }}</span></div>
        </div>
        <div class="col-half">
            <div class="field-label">Instituição</div>
            <div class="field-line">{{ $student->institution?->name ?? '' }}</div>
        </div>
    </div>
    
    <div class="section-title">Localização</div>
    <div class="row">
        <div class="col-third">
            <div class="field-label">CIA</div>
            <div class="field-line">{{ $student->cia ? $student->cia . 'ª CIA' : '-' }}</div>
        </div>
        <div class="col-third">
            <div class="field-label">Pelotão</div>
            <div class="field-line">{{ $student->platoon ? $student->platoon . 'º Pelotão' : '-' }}</div>
        </div>
        <div class="col-third">
            <div class="field-label">Secção</div>
            <div class="field-line">{{ $student->section ? $student->section . 'ª Secção' : '-' }}</div>
        </div>
    </div>
    
    @php
        $lastEnrollment = $student->classEnrollments->first();
        $courseName = $lastEnrollment?->studentClass?->courseMap?->course?->name ?? '-';
        $className = $lastEnrollment?->studentClass?->name ?? '-';
        $phaseName = $lastEnrollment?->coursePhase?->name ?? '-';
    @endphp
    
    <div class="section-title">Curso</div>
    <div class="field">
        <div class="field-label">Nome do Curso</div>
        <div class="field-line">{{ $courseName }}</div>
    </div>
    <div class="row">
        <div class="col-half">
            <div class="field-label">Turma</div>
            <div class="field-line">{{ $className }}</div>
        </div>
        <div class="col-half">
            <div class="field-label">Fase</div>
            <div class="field-line">{{ $phaseName }}</div>
        </div>
    </div>
    
    <div class="section-title">Disciplinas ({{ $student->subjectEnrollments->count() }})</div>
    @if($student->subjectEnrollments->count() > 0)
        @php
            $subjects = $student->subjectEnrollments;
            $half = ceil($subjects->count() / 2);
            $col1 = $subjects->take($half);
            $col2 = $subjects->skip($half);
        @endphp
        <div class="disc-container">
            <div class="disc-col">
                @foreach($col1 as $i => $e)
                    <div class="disc-item"><span class="disc-num">{{ $i + 1 }}.</span> {{ $e->subject?->name ?? '-' }}</div>
                @endforeach
            </div>
            <div class="disc-col">
                @foreach($col2 as $i => $e)
                    <div class="disc-item"><span class="disc-num">{{ $half + $i + 1 }}.</span> {{ $e->subject?->name ?? '-' }}</div>
                @endforeach
            </div>
        </div>
        <div style="clear: both;"></div>
    @else
        <p style="color: #888; font-size: 9pt;">Nenhuma disciplina inscrita.</p>
    @endif
    
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Assinatura do Formando</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Assinatura do Responsável</div>
        </div>
    </div>
    
    <div class="footer" style="clear: both;">
        Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
