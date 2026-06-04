<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Certificados de Conclusão</title>
    <style>
        @page { 
            size: A4 landscape; 
            margin: 0; 
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Times New Roman', Georgia, serif;
            background: #fff;
        }
        
        .certificado {
            width: 297mm;
            height: 210mm;
            position: relative;
            background: #fff;
            overflow: hidden;
            page-break-after: always;
        }
        
        .certificado:last-child {
            page-break-after: avoid;
        }
        
        .borda-externa {
            position: absolute;
            top: 6mm;
            left: 6mm;
            right: 6mm;
            bottom: 6mm;
            border: 5px solid #041B4E;
        }
        
        .borda-dourada {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px solid #B8860B;
        }
        
        .borda-interna {
            position: absolute;
            top: 14mm;
            left: 14mm;
            right: 14mm;
            bottom: 14mm;
            border: 2px solid #041B4E;
            background: #fff;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 280px;
            opacity: 0.06;
            background: url('/images/logo-pna.png') center/contain no-repeat;
            z-index: 1;
        }
        
        .conteudo {
            position: absolute;
            top: 18mm;
            left: 18mm;
            right: 18mm;
            bottom: 18mm;
            z-index: 2;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 4mm;
        }
        
        .logo-pna {
            width: 70px;
            height: auto;
            margin-bottom: 2mm;
        }
        
        .header-text p {
            font-size: 11px;
            color: #041B4E;
            margin: 0.5mm 0;
            letter-spacing: 0.5px;
        }
        
        .header-text .republica {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header-text .policia {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header-text .escola {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 1mm;
        }
        
        .titulo {
            text-align: center;
            margin: 5mm 0 4mm 0;
        }
        
        .titulo h1 {
            font-size: 46px;
            color: #041B4E;
            font-weight: bold;
            letter-spacing: 5px;
            text-transform: uppercase;
            border-bottom: 3px double #B8860B;
            display: inline-block;
            padding-bottom: 2mm;
        }
        
        .corpo {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 15mm;
        }
        
        .texto-principal {
            font-size: 13px;
            line-height: 1.9;
            text-align: justify;
            text-indent: 12mm;
            margin-bottom: 4mm;
        }
        
        .nome-aluno {
            font-size: 18px;
            font-weight: bold;
            color: #C00;
            text-transform: uppercase;
        }
        
        .nome-curso {
            font-weight: bold;
            color: #041B4E;
            text-transform: uppercase;
        }
        
        .notas-container {
            margin: 2mm 0;
        }
        
        .notas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1mm 12mm;
            font-size: 10px;
        }
        
        .nota-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted #999;
            padding: 1mm 0;
        }
        
        .nota-valor {
            font-weight: bold;
            min-width: 70px;
            text-align: right;
        }
        
        .nota-valor.vermelho { color: #C00; }
        .nota-valor.azul { color: #041B4E; }
        
        .rodape {
            margin-top: auto;
        }
        
        .local-data {
            text-align: center;
            font-size: 11px;
            font-style: italic;
            margin-bottom: 8mm;
        }
        
        .assinaturas {
            display: flex;
            justify-content: space-between;
            padding: 0 12mm;
        }
        
        .assinatura {
            text-align: center;
            width: 170px;
        }
        
        .assinatura-linha {
            width: 100%;
            height: 1px;
            background: #333;
            margin-bottom: 1mm;
        }
        
        .assinatura-cargo {
            font-size: 9px;
            font-weight: bold;
            color: #041B4E;
            text-transform: uppercase;
        }
        
        .assinatura-patente {
            font-size: 8px;
            font-style: italic;
        }
        
        .selo-central {
            position: absolute;
            bottom: 32mm;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }
        
        .selo-central img {
            width: 60px;
            height: auto;
            opacity: 0.7;
        }
        
        .selo-texto {
            font-size: 7px;
            color: #041B4E;
            font-weight: bold;
            margin-top: 1mm;
        }

        .numero-certificado {
            position: absolute;
            top: 18mm;
            right: 22mm;
            font-size: 9px;
            color: #666;
        }

        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @foreach($alunos as $aluno)
    <div class="certificado">
        <div class="borda-externa"></div>
        <div class="borda-dourada"></div>
        <div class="borda-interna"></div>
        <div class="watermark"></div>
        
        <div class="numero-certificado">
            Nº {{ $aluno['numero'] }}/CBFARFT/{{ date('Y') }}
        </div>
        
        <div class="conteudo">
            <div class="header">
                <img src="/images/logo-pna.png" alt="Logo PNA" class="logo-pna">
                <div class="header-text">
                    <p class="republica">República de Angola</p>
                    <p>Ministério do Interior</p>
                    <p class="policia">Polícia Nacional de Angola</p>
                    <p class="escola">{{ strtoupper($aluno['instituicao'] ?? 'Escola de Formação de Polícia') }}</p>
                </div>
            </div>
            
            <div class="titulo">
                <h1>Certificado</h1>
            </div>
            
            <div class="corpo">
                <div class="texto-principal">
                    O Director da {{ $aluno['instituicao'] ?? 'Escola de Formação de Polícia' }}, 
                    certifica que o(a) Sr.(a) <span class="nome-aluno">{{ $aluno['nome'] }}</span>, 
                    frequentou no ano de instrução {{ date('Y') }}, 
                    o "<span class="nome-curso">{{ $aluno['curso'] ?? 'Curso de Formação Policial' }}</span>", 
                    tendo ficado <strong>Apto(a)</strong>, conforme o registo nº <strong>{{ $aluno['numero'] }}/CBFARFT/{{ date('Y') }}</strong>, 
                    arquivada nesta Escola.
                    @if($aluno['cia'] || $aluno['pelotao'] || $aluno['seccao'])
                    <br>Classificação: 
                    @if($aluno['cia'])CIA {{ $aluno['cia'] }}@endif
                    @if($aluno['pelotao']) | Pelotão {{ $aluno['pelotao'] }}@endif
                    @if($aluno['seccao']) | Secção {{ $aluno['seccao'] }}@endif
                    @endif
                </div>
                
                <div class="notas-container">
                    <div class="notas-grid">
                        <div class="nota-item">
                            <span>Média Geral</span>
                            <span class="nota-valor azul">({{ $aluno['media'] }}) Valores</span>
                        </div>
                    </div>
                </div>
                
                <div class="rodape">
                    <div class="local-data">
                        <strong>{{ $aluno['instituicao'] ?? 'Escola de Formação de Polícia' }}</strong>, 
                        aos ______ de __________________ de {{ date('Y') }}.
                    </div>
                    
                    <div class="assinaturas">
                        <div class="assinatura">
                            <div class="assinatura-linha"></div>
                            <div class="assinatura-cargo">O Director Adj. P/ Instrução e Ensino</div>
                            <div class="assinatura-patente">*Subcomissário*</div>
                        </div>
                        <div class="assinatura">
                            <div class="assinatura-linha"></div>
                            <div class="assinatura-cargo">O Director da Escola</div>
                            <div class="assinatura-patente">**Comissário**</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>
