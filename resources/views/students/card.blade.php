<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartão de Identificação - {{ $student->candidate?->full_name ?? 'Formando' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .page-container {
            text-align: center;
        }

        .page-title {
            color: #fff;
            font-size: 24px;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 30px;
        }

        .card-container {
            width: 300px;
            height: 420px;
            perspective: 1000px;
            margin: 0 auto 30px;
        }

        .card {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s ease;
        }

        .card-container:hover .card {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 12px;
            backface-visibility: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            padding: 12px;
            box-sizing: border-box;
            background-color: #fff;
            color: #000;
            background-size: cover;
            background-position: center;
        }

        .front {
            background-image: url('/images/cards/fundo_card.png');
        }

        .back {
            transform: rotateY(180deg);
            font-size: 14px;
            padding: 16px;
            line-height: 1.5;
            overflow-y: auto;
            background-image: url('/images/cards/fundo_card.png');
        }

        .header {
            text-align: center;
        }

        .header img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .header h4 {
            margin: 4px 0;
            font-size: 9pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #041c4f;
        }

        .header h5, .header h6 {
            margin: 4px 0;
            font-size: 7pt;
            color: #333;
        }

        #passe-sede {
            font-size: 8pt;
            color: #041c4f;
            font-weight: 600;
        }

        .center-photo {
            width: 100px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            margin: 12px auto;
            border: 3px solid #041c4f;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .left-info {
            margin-top: 8px;
            margin-left: 8px;
            font-size: 15px;
            line-height: 1.6;
        }

        .left-info p {
            margin: 0;
            font-size: 8pt;
            margin-top: 2px;
        }

        .left-info .nome {
            color: #c41e3a;
            font-weight: 700;
        }

        .text-back {
            font-size: 9pt;
            text-align: center;
            color: #333;
        }

        .img-qr {
            width: 50px;
            height: 50px;
        }

        .codigo-barra {
            width: 140px;
            height: 25px;
            margin-top: 8px;
        }

        .assinatura {
            width: 120px;
            height: 60px;
            margin: 10px auto 0;
            display: block;
        }

        .back-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding-top: 40px;
        }

        .back-text-section {
            text-align: center;
        }

        .back-signature-section {
            text-align: center;
            margin-top: 20px;
        }

        .back-qr-section {
            position: absolute;
            bottom: 16px;
            left: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: linear-gradient(135deg, #041c4f 0%, #0a3d8f 100%);
            color: #fff;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(4, 28, 79, 0.4);
        }

        .btn-back {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .page-title, .page-subtitle, .action-buttons {
                display: none;
            }

            .card-container {
                width: 85mm;
                height: 54mm;
                perspective: none;
            }

            .card {
                transform: none !important;
            }

            .card-face {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            .back {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <h1 class="page-title">Cartão de Identificação</h1>
        <p class="page-subtitle">Passe o mouse sobre o cartão para ver o verso</p>

        <div class="card-container">
            <div class="card">
                <!-- Frente do Cartão -->
                <div class="card-face front">
                    <div class="header">
                        <br>
                        @if($institution && $institution->logo)
                            <img src="{{ asset('storage/' . $institution->logo) }}" alt="Logo da Escola">
                        @else
                            <img src="{{ asset('images/logo-policia.png') }}" alt="Logo da Escola">
                        @endif
                        <h4>{{ $institution->name ?? 'Escola de Polícia' }}</h4>
                        <h5>{{ $institution->province ?? '' }}{{ $institution->province && $institution->municipality ? ' / ' : '' }}{{ $institution->municipality ?? '' }}</h5>
                        <h6 id="passe-sede">PASSE DE IDENTIFICAÇÃO Nº{{ $student->id }}/{{ $courseCategory ?? 'CBP' }}/{{ $courseAbbreviation ?? 'EPP' }}/{{ $academicYear ?? date('Y') }}</h6>
                    </div>

                    @php
                        $photoUrl = null;
                        if ($student->photo) {
                            $photoUrl = asset('storage/' . $student->photo);
                        } elseif ($student->candidate?->photo) {
                            $photoUrl = asset('storage/' . $student->candidate->photo);
                        } else {
                            $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($student->candidate?->full_name ?? 'F') . '&background=041c4f&color=fff&size=200';
                        }
                    @endphp
                    <img src="{{ $photoUrl }}" 
                         alt="Foto do Formando" 
                         class="center-photo">

                    <div class="left-info">
                        <p><strong>Nome: <span class="nome">{{ $student->candidate?->full_name ?? 'N/A' }}</span></strong></p>
                        
                        @php
                            $studentType = strtolower($student->student_type ?? '');
                            $isRecrutaOrInstruendo = str_contains($studentType, 'recruta') || str_contains($studentType, 'instruendo');
                            $isFormandoSuperior = str_contains($studentType, 'formando') && str_contains($studentType, 'superior') || str_contains($studentType, 'formação');
                        @endphp
                        
                        @if($isRecrutaOrInstruendo)
                            {{-- Recrutas e Instruendos mostram NURI --}}
                            <p><strong>NURI: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @elseif($isFormandoSuperior)
                            {{-- Formandos Superiores e Em Formação mostram NIP --}}
                            <p><strong>NIP: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @else
                            {{-- Padrão mostra NURI --}}
                            <p><strong>NURI: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @endif
                        
                        @if($student->cia || $student->platoon || $student->section)
                        <p><strong>{{ $student->cia ? $student->cia . 'ª CIA' : '' }}{{ $student->cia && $student->platoon ? ' / ' : '' }}{{ $student->platoon ? $student->platoon . 'º Pelotão' : '' }}{{ ($student->cia || $student->platoon) && $student->section ? ' / ' : '' }}{{ $student->section ? $student->section . 'ª Secção' : '' }}</strong></p>
                        @endif

                        <img class="codigo-barra" src="/images/cards/codigo_barra.png" alt="Código de Barras">
                    </div>
                </div>

                <!-- Verso do Cartão -->
                <div class="card-face back">
                    <div class="back-content">
                        <div class="back-text-section">
                            <p class="text-back">Este passe identifica o portador na qualidade de formando da EPP.</p>
                            <br>
                            <p class="text-back"><strong>OBS:</strong> O passe só deverá ser exibido no interior da EPP e durante o processo docente educativo.</p>
                        </div>

                        <div class="back-signature-section">
                            <img class="assinatura" src="/images/cards/assinatura.png" alt="Assinatura">
                            <p class="text-back">O DIRECTOR NACIONAL</p>
                            <p class="text-back"><strong>MANUEL GREGÓRIO DE SOUSA</strong><br>** COMISSÁRIO **</p>
                        </div>

                        <div class="back-qr-section">
                            <img class="img-qr" src="/images/cards/qr_code.jpg" alt="QR Code">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-print" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Cartão
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Voltar
            </a>
        </div>
    </div>
</body>
</html>
