@php
    $type = $template->card_type;
    $orientation = $template->orientation ?: 'horizontal';
    $isVertical = $orientation === \App\Models\CardTemplate::ORIENTATION_VERTICAL;
    $frontText = $template->front_text_color ?: $template->text_color ?: '#ffffff';
    $backText = $template->back_text_color ?: '#111827';
    $frontBackgroundColor = $template->front_background_color ?: $template->primary_color ?: '#ffffff';
    $backBackgroundColor = $template->back_background_color ?: '#f8fafc';
    $frontStyle = $template->front_background_url
        ? "background-color: {$frontBackgroundColor}; background-image: url('{$template->front_background_url}');"
        : "background-color: {$frontBackgroundColor};";
    $backStyle = $template->back_background_url
        ? "background-color: {$backBackgroundColor}; background-image: url('{$template->back_background_url}');"
        : "background-color: {$backBackgroundColor};";
@endphp

@if ($type === \App\Models\CardTemplate::TYPE_STUDENT)
    <div class="sigef-card-preview" style="display: grid; justify-content: center; padding: 14px;">
        @include('students.partials.identification-card', ['mode' => 'template-preview'])
    </div>
@else
<div class="sigef-card-preview">
    <style>
        .sigef-card-preview {
            display: grid;
            gap: 18px;
            justify-content: center;
            padding: 14px;
        }

        .sigef-card-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, auto));
            gap: 18px;
            align-items: start;
        }

        .sigef-card-preview-face {
            position: relative;
            width: {{ $isVertical ? '258px' : '390px' }};
            height: {{ $isVertical ? '390px' : '258px' }};
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 14px;
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .sigef-card-preview-pad {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: {{ $isVertical ? '18px' : '16px 18px' }};
        }

        .sigef-card-preview-header {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 10px;
            align-items: center;
            min-height: 46px;
        }

        .sigef-card-preview-logo {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 10px;
            background: rgba(255, 255, 255, .88);
            color: #041c4f;
            font-size: 12px;
            font-weight: 800;
        }

        .sigef-card-preview-logo img,
        .sigef-card-preview-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sigef-card-preview-logo img {
            object-fit: contain;
        }

        .sigef-card-preview-brand {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.12;
            text-transform: uppercase;
        }

        .sigef-card-preview-subtitle {
            margin-top: 2px;
            font-size: 9px;
            line-height: 1.2;
            opacity: .9;
            text-transform: uppercase;
        }

        .sigef-card-preview-body {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '96px 1fr' }};
            gap: 13px;
            align-items: center;
            flex: 1;
        }

        .sigef-card-preview-photo {
            width: {{ $isVertical ? '112px' : '92px' }};
            height: {{ $isVertical ? '132px' : '112px' }};
            margin: {{ $isVertical ? '0 auto' : '0' }};
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, .9);
            border-radius: 10px;
            background: rgba(255, 255, 255, .92);
            color: #041c4f;
            font-size: 30px;
            font-weight: 800;
        }

        .sigef-card-preview-role {
            width: max-content;
            max-width: 100%;
            padding: 5px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .88);
            color: #041c4f;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .sigef-card-preview-name {
            margin-top: 8px;
            font-size: {{ $isVertical ? '15px' : '16px' }};
            font-weight: 900;
            line-height: 1.08;
            text-transform: uppercase;
        }

        .sigef-card-preview-lines {
            margin-top: 10px;
            display: grid;
            gap: 5px;
            font-size: 10px;
            line-height: 1.25;
        }

        .sigef-card-preview-lines span {
            font-weight: 800;
            opacity: .92;
        }

        .sigef-card-preview-number {
            margin-top: auto;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .sigef-card-preview-back-title {
            color: {{ $backText }};
            font-size: 16px;
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-card-preview-back-text {
            margin: 18px auto 0;
            max-width: 84%;
            color: {{ $backText }};
            font-size: 12px;
            line-height: 1.45;
            text-align: center;
        }

        .sigef-card-preview-signature {
            margin-top: auto;
            display: grid;
            justify-items: center;
            gap: 4px;
            color: {{ $backText }};
            font-size: 10px;
            text-align: center;
        }

        .sigef-card-preview-signature img {
            width: 106px;
            height: 44px;
            object-fit: contain;
        }

        .sigef-card-preview-qr {
            position: absolute;
            right: 16px;
            bottom: 16px;
            width: 48px;
            height: 48px;
            border: 5px solid #fff;
            border-radius: 8px;
            background:
                linear-gradient(90deg, #111827 8px, transparent 8px) 0 0 / 16px 16px,
                linear-gradient(#111827 8px, transparent 8px) 0 0 / 16px 16px,
                #fff;
        }

        @media (max-width: 760px) {
            .sigef-card-preview-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="sigef-card-preview-grid">
        <section class="sigef-card-preview-face" style="{{ $frontStyle }}; color: {{ $frontText }};">
            <div class="sigef-card-preview-pad">
                <header class="sigef-card-preview-header">
                    <div class="sigef-card-preview-logo">
                        @if ($template->logo_url)
                            <img src="{{ $template->logo_url }}" alt="Logo">
                        @else
                            SIGEF
                        @endif
                    </div>
                    <div>
                        <div class="sigef-card-preview-brand">{{ $template->brand_name ?: 'SIGEF' }}</div>
                        @if ($template->subtitle)
                            <div class="sigef-card-preview-subtitle">{{ $template->subtitle }}</div>
                        @endif
                    </div>
                </header>

                <div class="sigef-card-preview-body">
                    <div class="sigef-card-preview-photo">
                        @if ($payload['photo_url'])
                            <img src="{{ $payload['photo_url'] }}" alt="{{ $payload['name'] }}">
                        @else
                            {{ $payload['initials'] ?: 'SG' }}
                        @endif
                    </div>

                    <div>
                        <div class="sigef-card-preview-role">
                            {{ \App\Models\CardTemplate::cardTypeOptions()[$type] ?? 'Cartao' }}
                        </div>
                        @if ($template->front_title)
                            <div class="sigef-card-preview-lines">
                                <div><span>{{ $template->front_title }}</span></div>
                            </div>
                        @endif
                        <div class="sigef-card-preview-name">{{ $payload['name'] }}</div>
                        <div class="sigef-card-preview-lines">
                            @if ($type === \App\Models\CardTemplate::TYPE_STUDENT)
                                <div><span>CURSO:</span> {{ $payload['course'] }}</div>
                                <div><span>TURMA:</span> {{ $payload['class'] }}</div>
                            @elseif ($type === \App\Models\CardTemplate::TYPE_PROFESSOR)
                                <div><span>DISCIPLINA:</span> {{ $payload['discipline'] }}</div>
                                <div><span>DEPARTAMENTO:</span> {{ $payload['department'] }}</div>
                            @else
                                <div><span>DEPARTAMENTO:</span> {{ $payload['department'] }}</div>
                                <div><span>FUNCAO:</span> {{ $payload['function'] }}</div>
                                <div><span>GRUPO SANGUINEO:</span> {{ $payload['blood_type'] }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="sigef-card-preview-number">{{ $template->number_label ?: 'Nº' }} {{ $payload['number'] }}</div>
            </div>
        </section>

        <section class="sigef-card-preview-face" style="{{ $backStyle }};">
            <div class="sigef-card-preview-pad">
                @if ($template->back_title)
                    <div class="sigef-card-preview-back-title">{{ $template->back_title }}</div>
                @endif

                @if ($template->footer_text)
                    <div class="sigef-card-preview-back-text">{{ $template->footer_text }}</div>
                @endif

                <div class="sigef-card-preview-signature">
                    @if ($template->signature_label)
                        <strong>{{ $template->signature_label }}</strong>
                    @endif
                    @if ($template->signature_image_url)
                        <img src="{{ $template->signature_image_url }}" alt="Assinatura">
                    @endif
                    @if ($template->signatory_name)
                        <strong>{{ $template->signatory_name }}</strong>
                    @endif
                    @if ($template->signatory_title)
                        <span>{{ $template->signatory_title }}</span>
                    @endif
                </div>

                @if ($template->show_qr_code)
                    <div class="sigef-card-preview-qr" aria-hidden="true"></div>
                @endif
            </div>
        </section>
    </div>
</div>
@endif
