<div class="card-modal-container" style="display: flex; justify-content: center; padding: 20px;">
    <style>
        .card-modal-wrapper {
            width: 280px;
            height: 400px;
            perspective: 1000px;
        }

        .card-modal {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s ease;
        }

        .card-modal-wrapper:hover .card-modal {
            transform: rotateY(180deg);
        }

        .card-modal-face {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 12px;
            backface-visibility: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            padding: 12px;
            box-sizing: border-box;
            background-color: #fff;
            color: #000;
            background-size: cover;
            background-position: center;
            background-image: url('{{ asset('images/cards/fundo_card.png') }}');
        }

        .card-modal-back {
            transform: rotateY(180deg);
            font-size: 14px;
            padding: 16px;
            line-height: 1.5;
        }

        .card-modal-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card-modal-header img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 6px;
            display: block;
        }

        .card-modal-header h4 {
            margin: 4px 0;
            font-size: 10pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #041c4f;
        }

        .card-modal-header h5 {
            margin: 2px 0;
            font-size: 8pt;
            color: #333;
        }

        .card-modal-header h6 {
            margin: 2px 0;
            font-size: 8pt;
            color: #041c4f;
            font-weight: 600;
        }

        .card-modal-photo {
            width: 80px;
            height: 100px;
            border-radius: 6px;
            object-fit: cover;
            margin: 8px auto;
            border: 2px solid #041c4f;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .card-modal-info {
            margin-top: 6px;
            margin-left: 6px;
            font-size: 9pt;
            line-height: 1.5;
        }

        .card-modal-info p {
            margin: 3px 0;
        }

        .card-modal-info .nome {
            color: #c41e3a;
            font-weight: 700;
        }

        .card-modal-barcode {
            width: 120px;
            height: 20px;
            margin-top: 6px;
        }

        .card-modal-back-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding-top: 30px;
        }

        .card-modal-text-back {
            font-size: 8pt;
            text-align: center;
            color: #333;
        }

        .card-modal-signature {
            width: 100px;
            height: 50px;
            margin: 8px auto 0;
            display: block;
        }

        .card-modal-qr {
            width: 40px;
            height: 40px;
            position: absolute;
            bottom: 12px;
            left: 12px;
        }

        .card-modal-flip-hint {
            text-align: center;
            color: #666;
            font-size: 11px;
            margin-top: 10px;
        }
    </style>

    <div>
        <div class="card-modal-wrapper">
            <div class="card-modal">
                {{-- Frente do Cartão --}}
                <div class="card-modal-face card-modal-front">
                    <div class="card-modal-header">
                        @if($institution && $institution->logo)
                            <img src="{{ asset('storage/' . $institution->logo) }}" alt="Logo">
                        @else
                            <img src="{{ asset('images/logo-policia.png') }}" alt="Logo">
                        @endif
                        <h4>{{ $institution->name ?? 'Escola de Polícia' }}</h4>
                        <h5>{{ $institution->province ?? '' }}{{ $institution->province && $institution->municipality ? ' / ' : '' }}{{ $institution->municipality ?? '' }}</h5>
                        <h6>PASSE DE IDENTIFICAÇÃO Nº{{ $student->id }}/{{ $courseCategory ?? 'CBP' }}/{{ $courseAbbreviation ?? 'EPP' }}/{{ $academicYear ?? date('Y') }}</h6>
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
                    <img src="{{ $photoUrl }}" alt="Foto" class="card-modal-photo">

                    <div class="card-modal-info">
                        <p><strong>Nome: <span class="nome">{{ $student->candidate?->full_name ?? 'N/A' }}</span></strong></p>
                        
                        @php
                            $studentType = strtolower($student->student_type ?? '');
                            $isRecrutaOrInstruendo = str_contains($studentType, 'recruta') || str_contains($studentType, 'instruendo');
                            $isFormandoSuperior = str_contains($studentType, 'formando') && str_contains($studentType, 'superior') || str_contains($studentType, 'formação');
                        @endphp
                        
                        @if($isRecrutaOrInstruendo)
                            <p><strong>NURI: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @elseif($isFormandoSuperior)
                            <p><strong>NIP: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @else
                            <p><strong>NURI: {{ $student->nuri ?? 'N/A' }}</strong></p>
                        @endif
                        
                        @if($student->cia || $student->platoon || $student->section)
                        <p><strong>{{ $student->cia ? $student->cia . 'ª CIA' : '' }}{{ $student->cia && $student->platoon ? ' / ' : '' }}{{ $student->platoon ? $student->platoon . 'º Pel.' : '' }}{{ ($student->cia || $student->platoon) && $student->section ? ' / ' : '' }}{{ $student->section ? $student->section . 'ª Sec.' : '' }}</strong></p>
                        @endif

                        <img class="card-modal-barcode" src="{{ asset('images/cards/codigo_barra.png') }}" alt="Código">
                    </div>
                </div>

                {{-- Verso do Cartão --}}
                <div class="card-modal-face card-modal-back">
                    <div class="card-modal-back-content">
                        <div>
                            <p class="card-modal-text-back">Este passe identifica o portador na qualidade de formando da EPP.</p>
                            <br>
                            <p class="card-modal-text-back"><strong>OBS:</strong> O passe só deverá ser exibido no interior da EPP e durante o processo docente educativo.</p>
                        </div>

                        <div style="text-align: center; margin-top: 15px;">
                            <img class="card-modal-signature" src="{{ asset('images/cards/assinatura.png') }}" alt="Assinatura">
                            <p class="card-modal-text-back">O DIRECTOR NACIONAL</p>
                            <p class="card-modal-text-back"><strong>MANUEL GREGÓRIO DE SOUSA</strong><br>** COMISSÁRIO **</p>
                        </div>

                        <img class="card-modal-qr" src="{{ asset('images/cards/qr_code.jpg') }}" alt="QR">
                    </div>
                </div>
            </div>
        </div>
        <p class="card-modal-flip-hint">Passe o mouse sobre o cartão para ver o verso</p>
    </div>
</div>
