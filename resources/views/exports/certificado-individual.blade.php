@php
    $autoPrint = $autoPrint ?? false;
    $embeddedMode = $embeddedMode ?? false;
    $certificate = $certificate ?? [];
    $certificateHeaderLines = array_values(array_filter($certificate['header_lines'] ?? []));
    $certificateLogoUrl = $certificate['logo_url'] ?? null;
    $certificateWatermarkUrl = $certificate['watermark_url'] ?? null;
    $certificateInstitutionName = $certificate['name'] ?? ($aluno['instituicao'] ?? 'Escola de Formação de Polícia');
    $certificateDirectorTitle = $certificate['director_title'] ?? ($aluno['director'] ?? 'O Director');
    $certificateDirectorName = $certificate['director_name'] ?? '';
    $certificateDirectorLabel = trim($certificateDirectorTitle . ($certificateDirectorName ? ', ' . $certificateDirectorName : ''));
    $certificateLocation = $certificate['location'] ?? ($aluno['cidade'] ?? 'Luanda');
    $certificateLeftSignatureTitle = $certificate['left_signature_title'] ?? 'O Director Adj. P/ Instrução e Ensino';
    $certificateLeftSignatureSubtitle = $certificate['left_signature_subtitle'] ?? '*Subcomissário*';
    $certificateRightSignatureTitle = $certificate['right_signature_title'] ?? 'O Director da Escola';
    $certificateRightSignatureSubtitle = $certificate['right_signature_subtitle'] ?? '**Comissário**';
@endphp
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Certificado - {{ $aluno['nome'] }}</title>
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
            min-height: 100vh;
        }

        .certificado {
            width: 297mm;
            height: 210mm;
            position: relative;
            background: #fff;
            overflow: hidden;
        }

        /* Borda decorativa externa azul */
        .borda-externa {
            position: absolute;
            top: 6mm;
            left: 6mm;
            right: 6mm;
            bottom: 6mm;
            border: 5px solid #041B4E;
        }

        /* Borda dourada */
        .borda-dourada {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px solid #B8860B;
        }

        /* Borda interna azul fina */
        .borda-interna {
            position: absolute;
            top: 14mm;
            left: 14mm;
            right: 14mm;
            bottom: 14mm;
            border: 2px solid #041B4E;
            background: #fff;
        }

        /* Logo watermark no centro */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 280px;
            opacity: 0.06;
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            z-index: 1;
        }

        /* Conteúdo principal */
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

        /* Cabeçalho */
        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .logo-pna {
            width: 80px;
            height: auto;
            margin-bottom: 3mm;
        }

        .header-text {
            text-align: center;
        }

        .header-text p {
            font-size: 12px;
            color: #041B4E;
            margin: 1mm 0;
            letter-spacing: 1px;
        }

        .header-text .republica {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-text .ministerio {
            font-size: 12px;
        }

        .header-text .policia {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-text .escola {
            font-size: 14px;
            font-weight: bold;
            color: #041B4E;
            text-transform: uppercase;
            margin-top: 2mm;
        }

        /* Título do certificado */
        .titulo {
            text-align: center;
            margin: 4mm 0 3mm 0;
        }

        .titulo h1 {
            font-size: 46px;
            color: #041B4E;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            font-family: 'Times New Roman', serif;
            border-bottom: 3px double #B8860B;
            display: inline-block;
            padding-bottom: 2mm;
        }

        /* Corpo do certificado */
        .corpo {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 20mm;
        }

        .texto-principal {
            font-size: 13px;
            line-height: 1.8;
            text-align: justify;
            text-indent: 15mm;
            margin-bottom: 3mm;
        }

        .nome-aluno {
            font-size: 20px;
            font-weight: bold;
            color: #C00;
            text-transform: uppercase;
        }

        .nome-curso {
            font-weight: bold;
            color: #041B4E;
            text-transform: uppercase;
        }

        /* Tabela de notas */
        .notas-container {
            margin: 2mm 0;
        }

        .notas-titulo {
            font-size: 10px;
            font-weight: bold;
            color: #041B4E;
            margin-bottom: 1mm;
        }

        .notas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0mm 15mm;
            font-size: 10px;
        }

        .nota-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted #999;
            padding: 0.5mm 0;
        }

        .nota-nome {
            flex: 1;
        }

        .nota-valor {
            font-weight: bold;
            min-width: 80px;
            text-align: right;
        }

        .nota-valor.vermelho {
            color: #C00;
        }

        .nota-valor.azul {
            color: #041B4E;
        }

        /* Rodapé */
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
            padding: 0 15mm;
        }

        .assinatura {
            text-align: center;
            width: 180px;
        }

        .assinatura-linha {
            width: 100%;
            height: 1px;
            background: #333;
            margin-bottom: 2mm;
        }

        .assinatura-cargo {
            font-size: 10px;
            font-weight: bold;
            color: #041B4E;
            text-transform: uppercase;
        }

        .assinatura-patente {
            font-size: 9px;
            font-style: italic;
        }

        /* Selo central */
        .selo-central {
            position: absolute;
            bottom: 35mm;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .selo-central img {
            width: 70px;
            height: auto;
            opacity: 0.7;
        }

        .selo-texto {
            font-size: 8px;
            color: #041B4E;
            font-weight: bold;
            margin-top: 1mm;
        }

        /* Número do certificado */
        .numero-certificado {
            position: absolute;
            top: 20mm;
            right: 25mm;
            font-size: 10px;
            color: #666;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }

        @if($embeddedMode)
        @media screen {
            body {
                display: flex;
                justify-content: center;
                align-items: flex-start;
                min-width: max-content;
                padding: 16px;
                background: #eef3f8;
            }

            .certificado {
                flex: 0 0 auto;
                box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
            }
        }
        @endif
    </style>
</head>

<body>
    <div class="certificado">
        <div class="borda-externa"></div>
        <div class="borda-dourada"></div>
        <div class="borda-interna"></div>
        @if($certificateWatermarkUrl)
            <div class="watermark" style="background-image: url('{{ $certificateWatermarkUrl }}');"></div>
        @endif

        <div class="numero-certificado">
            Nº {{ $aluno['numero_registo'] ?? $aluno['numero'] }}/CBFARFT/{{ date('Y') }}
        </div>

        <div class="conteudo">
            <div class="header">
                @if($certificateLogoUrl)
                    <img src="{{ $certificateLogoUrl }}" alt="Logotipo" class="logo-pna">
                @endif
                <div class="header-text">
                    @foreach($certificateHeaderLines as $line)
                        <p class="{{ $loop->first ? 'republica' : ($loop->last ? 'escola' : 'policia') }}">
                            {{ mb_strtoupper((string) $line, 'UTF-8') }}
                        </p>
                    @endforeach
                    @if(false)
                    <p class="republica">República de Angola</p>
                    <p class="ministerio">Ministério do Interior</p>
                    <p class="policia">Polícia Nacional de Angola</p>
                    <p class="escola">{{ strtoupper($aluno['instituicao'] ?? 'Escola de Formação de Polícia') }}</p>
                    @endif
                </div>
            </div>

            <div class="titulo">
                <h1>Certificado</h1>
            </div>

            <div class="corpo">
                <div class="texto-principal">
                    <strong>{{ $aluno['director'] ?? 'O Director' }}</strong>, Director da {{ $aluno['instituicao'] ?? 'Escola de Formação de Polícia' }},
                    certifica que o(a) Sr.(a) <span class="nome-aluno">{{ $aluno['nome'] }}</span>,
                    frequentou no ano de instrução {{ $aluno['ano_instrucao'] ?? date('Y') }},
                    o "<span class="nome-curso">{{ $aluno['curso'] ?? 'Curso de Formação Policial' }}</span>",
                    tendo ficado <strong>Apto(a)</strong>, conforme o registo nº <strong>{{ $aluno['numero_registo'] ?? $aluno['numero'] }}/CBFARFT/{{ date('Y') }}</strong>,
                    arquivada nesta Escola, cujas médias por disciplinas abaixo se discriminam:
                </div>

                @if(isset($aluno['disciplinas']) && count($aluno['disciplinas']) > 0)
                <div class="notas-container">
                    <div class="notas-grid">
                        @php $disciplinas = $aluno['disciplinas']; @endphp
                        @foreach($disciplinas as $disciplina)
                        <div class="nota-item">
                            <span class="nota-nome">{{ $disciplina['nome'] }}</span>
                            <span class="nota-valor {{ $disciplina['nota'] >= 10 ? 'azul' : 'vermelho' }}">({{ number_format($disciplina['nota'], 0) }}) Valores</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="notas-container">
                    <div class="notas-grid">
                        <div class="nota-item">
                            <span class="nota-nome">Média Geral</span>
                            <span class="nota-valor azul">({{ $aluno['media'] }}) Valores</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="rodape">
                    <div class="local-data">
                        <strong>{{ $certificateInstitutionName }}</strong> em {{ $certificateLocation }},
                        aos ______ de __________________ de {{ date('Y') }}.
                    </div>

                    <div class="assinaturas">
                        <div class="assinatura">
                            <div class="assinatura-linha"></div>
                            <div class="assinatura-cargo">{{ $certificateLeftSignatureTitle }}</div>
                            <div class="assinatura-patente">{{ $certificateLeftSignatureSubtitle }}</div>
                        </div>
                        <div class="assinatura">
                            <div class="assinatura-linha"></div>
                            <div class="assinatura-cargo">{{ $certificateRightSignatureTitle }}</div>
                            <div class="assinatura-patente">{{ $certificateRightSignatureSubtitle }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($autoPrint)
    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.focus();
                window.print();
            }, 250);
        });
    </script>
    @endif
</body>

</html>
