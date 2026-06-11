@php
    $mode = $mode ?? 'modal';
    $orientation = $template->orientation ?: 'vertical';
    $isVertical = $orientation === \App\Models\CardTemplate::ORIENTATION_VERTICAL;
    $frontText = $template->front_text_color ?: $template->text_color ?: '#001b4d';
    $backText = $template->back_text_color ?: '#111827';
    $frontBackgroundColor = $template->front_background_color ?: $template->primary_color ?: '#ffffff';
    $backBackgroundColor = $template->back_background_color ?: '#ffffff';
    $frontStyle = $template->front_background_url
        ? "background-color: {$frontBackgroundColor}; background-image: url('{$template->front_background_url}');"
        : "background-color: {$frontBackgroundColor};";
    $backStyle = $template->back_background_url
        ? "background-color: {$backBackgroundColor}; background-image: url('{$template->back_background_url}');"
        : "background-color: {$backBackgroundColor};";
    $studentPlacementItems = collect([
        ['CIA', filled($payload['cia'] ?? null) ? $payload['cia'] : '-'],
        ['PELOTÃO', filled($payload['platoon'] ?? null) ? $payload['platoon'] : '-'],
        ['SECÇÃO', filled($payload['section'] ?? null) ? $payload['section'] : '-'],
    ]);
@endphp

<div class="sigef-student-card-shell sigef-student-card-{{ $mode }}">
    <style>
        .sigef-student-card-shell {
            --sigef-card-width: {{ $isVertical ? '280px' : '430px' }};
            --sigef-card-height: {{ $isVertical ? '400px' : '270px' }};
            display: grid;
            justify-content: center;
            gap: 10px;
            padding: {{ $mode === 'modal' ? '10px 0 4px' : '0' }};
            color: #111827;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .sigef-student-card-wrapper {
            display: grid;
            grid-template-columns: repeat(2, var(--sigef-card-width));
            gap: 14px;
            justify-content: center;
            width: max-content;
            max-width: 100%;
            perspective: none;
        }

        .sigef-student-card {
            display: contents;
        }

        .sigef-student-card-face {
            position: relative;
            width: var(--sigef-card-width);
            height: var(--sigef-card-height);
            display: flex;
            flex-direction: column;
            gap: {{ $isVertical ? '8px' : '7px' }};
            padding: {{ $isVertical ? '12px' : '12px 14px' }};
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 12px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .22);
        }

        .sigef-student-card-back {
            transform: none;
        }

        .sigef-student-card-header {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '56px 1fr' }};
            gap: 8px;
            align-items: center;
            text-align: {{ $isVertical ? 'center' : 'left' }};
        }

        .sigef-student-card-logo {
            width: {{ $isVertical ? '66px' : '54px' }};
            height: {{ $isVertical ? '66px' : '54px' }};
            margin: {{ $isVertical ? '0 auto' : '0' }};
            object-fit: contain;
        }

        .sigef-student-card-institution {
            font-size: {{ $isVertical ? '10px' : '11px' }};
            line-height: 1.18;
            font-weight: 800;
            text-transform: uppercase;
        }

        .sigef-student-card-location {
            margin-top: 2px;
            font-size: 8px;
            font-weight: 600;
            opacity: .76;
        }

        .sigef-student-card-number {
            margin-top: 3px;
            font-size: 8px;
            font-weight: 800;
            color: {{ $frontText }};
            text-transform: uppercase;
        }

        .sigef-student-card-body {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '94px 1fr' }};
            gap: {{ $isVertical ? '8px' : '13px' }};
            align-items: center;
            min-height: 0;
            flex: 1;
        }

        .sigef-student-card-photo {
            width: {{ $isVertical ? '86px' : '88px' }};
            height: {{ $isVertical ? '106px' : '108px' }};
            margin: {{ $isVertical ? '0 auto' : '0' }};
            border: 2px solid {{ $template->primary_color ?: '#041c4f' }};
            border-radius: 7px;
            object-fit: cover;
            background: #e5e7eb;
        }

        .sigef-student-card-info {
            display: grid;
            gap: 4px;
            min-width: 0;
            font-size: {{ $isVertical ? '9px' : '10px' }};
            line-height: 1.25;
            color: {{ $frontText }};
        }

        .sigef-student-card-name {
            color: {{ $template->secondary_color ?: '#c41e3a' }};
            font-size: {{ $isVertical ? '11px' : '13px' }};
            font-weight: 900;
            line-height: 1.12;
            text-transform: uppercase;
        }

        .sigef-student-card-info strong {
            font-weight: 900;
        }

        .sigef-student-card-placement-row {
            display: block;
            min-width: 0;
            font-size: inherit;
            line-height: inherit;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-student-card-placement-row .sigef-student-card-placement-separator {
            margin: 0 2px;
            font-weight: 900;
        }

        .sigef-student-card-back-content {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: 100%;
            color: {{ $backText }};
            text-align: center;
        }

        .sigef-student-card-back-title {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .sigef-student-card-back-text {
            margin: {{ $isVertical ? '26px auto 0' : '18px auto 0' }};
            max-width: 86%;
            font-size: {{ $isVertical ? '10px' : '11px' }};
            line-height: 1.45;
            white-space: pre-line;
        }

        .sigef-student-card-signature {
            margin-top: auto;
            display: grid;
            justify-items: center;
            gap: 3px;
            font-size: 9px;
            line-height: 1.25;
        }

        .sigef-student-card-signature img {
            width: {{ $isVertical ? '108px' : '122px' }};
            height: {{ $isVertical ? '48px' : '48px' }};
            object-fit: contain;
        }

        .sigef-student-card-qr {
            position: absolute;
            left: 12px;
            bottom: 12px;
            width: {{ $isVertical ? '46px' : '50px' }};
            height: {{ $isVertical ? '46px' : '50px' }};
            object-fit: contain;
            background: #fff;
            border: 4px solid #fff;
            border-radius: 7px;
        }

        .sigef-student-card-hint {
            margin: 0;
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }

        @media (max-width: 760px) {
            .sigef-student-card-wrapper {
                grid-template-columns: var(--sigef-card-width);
                width: var(--sigef-card-width);
            }
        }

        @media print {
            .sigef-student-card-shell {
                padding: 0;
            }

            .sigef-student-card-wrapper {
                width: {{ $isVertical ? '54mm' : '85mm' }};
                height: {{ $isVertical ? '85mm' : '54mm' }};
                perspective: none;
            }

            .sigef-student-card {
                transform: none !important;
            }

            .sigef-student-card-face {
                box-shadow: none;
                border: 1px solid #cbd5e1;
            }

            .sigef-student-card-back {
                display: none;
            }

            .sigef-student-card-hint {
                display: none;
            }
        }
    </style>

    <div class="sigef-student-card-wrapper">
        <div class="sigef-student-card">
            <section class="sigef-student-card-face sigef-student-card-front" style="{{ $frontStyle }} color: {{ $frontText }};">
                <header class="sigef-student-card-header">
                    <img class="sigef-student-card-logo" src="{{ $payload['logo_url'] }}" alt="Logo">
                    <div>
                        <div class="sigef-student-card-institution">{{ $payload['institution_name'] }}</div>
                        @if ($payload['institution_location'])
                            <div class="sigef-student-card-location">{{ $payload['institution_location'] }}</div>
                        @endif
                        <div class="sigef-student-card-number">
                            {{ $payload['front_title'] }} {{ $payload['number_label'] }}{{ $payload['card_number'] }}
                        </div>
                    </div>
                </header>

                <div class="sigef-student-card-body">
                    <img class="sigef-student-card-photo" src="{{ $payload['photo_url'] }}" alt="Foto de {{ $payload['name'] }}">

                    <div class="sigef-student-card-info">
                        <div><strong>Nome:</strong></div>
                        <div class="sigef-student-card-name">{{ $payload['name'] }}</div>
                        <div><strong>{{ $payload['document_label'] }}:</strong> {{ $payload['document_number'] }}</div>
                        @if ($payload['course'])
                            <div><strong>Curso:</strong> {{ $payload['course'] }}</div>
                        @endif
                        @if ($payload['class'])
                            <div><strong>Turma:</strong> {{ $payload['class'] }}</div>
                        @endif
                        <div class="sigef-student-card-placement-row">
                            @foreach ($studentPlacementItems as [$label, $value])
                                @if (! $loop->first)
                                    <span class="sigef-student-card-placement-separator">/</span>
                                @endif
                                <strong>{{ $label }}:</strong> {{ $value }}
                            @endforeach
                        </div>
                        @if ($payload['academic_year'] ?? null)
                            <div><strong>Ano Académico:</strong> {{ $payload['academic_year'] }}</div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="sigef-student-card-face sigef-student-card-back" style="{{ $backStyle }}">
                <div class="sigef-student-card-back-content">
                    @if ($template->back_title)
                        <div class="sigef-student-card-back-title">{{ $template->back_title }}</div>
                    @endif

                    <div class="sigef-student-card-back-text">{{ $payload['footer_text'] }}</div>

                    <div class="sigef-student-card-signature">
                        @if ($payload['signature_url'])
                            <img src="{{ $payload['signature_url'] }}" alt="Assinatura">
                        @endif
                        @if ($payload['signature_label'])
                            <span>{{ $payload['signature_label'] }}</span>
                        @endif
                        @if ($payload['signatory_name'])
                            <strong>{{ $payload['signatory_name'] }}</strong>
                        @endif
                        @if ($payload['signatory_title'])
                            <span>{{ $payload['signatory_title'] }}</span>
                        @endif
                    </div>

                    @if ($payload['show_qr_code'] && $payload['qr_code_uri'])
                        <img class="sigef-student-card-qr" src="{{ $payload['qr_code_uri'] }}" alt="QR code">
                    @endif
                </div>
            </section>
        </div>
    </div>

    @if ($mode === 'modal')
        <p class="sigef-student-card-hint">Frente e verso do cartao</p>
    @endif
</div>
