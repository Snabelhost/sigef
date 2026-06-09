@php
    $orientation = $template->orientation ?: \App\Models\CardTemplate::ORIENTATION_HORIZONTAL;
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
    $screenWidth = $isVertical ? '306px' : '506px';
    $screenHeight = $isVertical ? '485px' : '320px';
    $printWidth = $isVertical ? '54mm' : '85.6mm';
    $printHeight = $isVertical ? '85.6mm' : '54mm';
    $orientationLabel = $isVertical ? 'RETRATO' : 'PAISAGEM';
@endphp

<div
    id="{{ $viewerId }}"
    class="sigef-trainer-card-modal {{ $isVertical ? 'is-vertical' : 'is-horizontal' }}"
    x-data="{
        side: 'front',
        isExpanded: false,
        get sideLabel() {
            return this.side === 'front' ? 'Frente' : 'Verso';
        },
        toggleSide() {
            this.side = this.side === 'front' ? 'back' : 'front';
        },
        toggleExpand() {
            this.isExpanded = ! this.isExpanded;

            let el = this.$el;

            while (el && el !== document.body) {
                el = el.parentElement;

                if (el && el.className && el.className.toString().indexOf('fi-modal-window') >= 0) {
                    el.classList.toggle('sigef-trainer-card-expanded', this.isExpanded);
                    break;
                }
            }
        },
        printCard() {
            window.print();
        },
    }"
>
    <style>
        .sigef-trainer-card-modal {
            --trainer-card-screen-width: {{ $screenWidth }};
            --trainer-card-screen-height: {{ $screenHeight }};
            --trainer-card-print-width: {{ $printWidth }};
            --trainer-card-print-height: {{ $printHeight }};
            --trainer-card-primary: {{ $template->primary_color ?: '#041c4f' }};
            --trainer-card-secondary: {{ $template->secondary_color ?: '#2563eb' }};
            display: grid;
            gap: 16px;
            color: #0f172a;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .trainer-card-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e293b 0%, #071a44 100%);
            color: #f8fafc;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
        }

        .trainer-card-title-wrap {
            display: grid;
            gap: 9px;
            min-width: 0;
        }

        .trainer-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            font-size: 13px;
            font-weight: 800;
        }

        .trainer-card-title span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .trainer-card-toolbar-icon,
        .trainer-card-button-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .trainer-card-badges,
        .trainer-card-actions,
        .trainer-card-side-switch {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .trainer-card-badge {
            display: inline-flex;
            align-items: center;
            min-height: 20px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .trainer-card-badge.primary {
            background: #2563eb;
            color: #fff;
        }

        .trainer-card-badge.muted {
            background: rgba(255, 255, 255, .12);
            color: #dbeafe;
            border: 1px solid rgba(255, 255, 255, .14);
        }

        .trainer-card-badge.success {
            background: #16a34a;
            color: #fff;
        }

        .trainer-card-badge.danger {
            background: #dc2626;
            color: #fff;
        }

        .trainer-card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 34px;
            padding: 8px 14px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 7px;
            background: rgba(255, 255, 255, .08);
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .18s ease;
        }

        .trainer-card-button:hover {
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .trainer-card-button.accent {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }

        .trainer-card-button.accent:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .trainer-card-stage {
            display: grid;
            justify-content: center;
            align-content: center;
            min-height: 560px;
            padding: 36px 18px 28px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef4ff 100%);
            overflow: auto;
        }

        .trainer-card-scene {
            width: var(--trainer-card-screen-width);
            height: var(--trainer-card-screen-height);
            perspective: 1100px;
        }

        .trainer-card-stack {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
        }

        .trainer-card-face {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 12px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 24px 50px rgba(15, 23, 42, .18);
            opacity: 0;
            pointer-events: none;
            transform: rotateY(10deg) translateY(8px);
            transition: opacity .25s ease, transform .25s ease;
        }

        .trainer-card-face.is-active {
            opacity: 1;
            pointer-events: auto;
            transform: rotateY(0deg) translateY(0);
        }

        .trainer-card-front {
            padding: {{ $isVertical ? '18px 18px 16px' : '18px 20px' }};
        }

        .trainer-card-back {
            padding: {{ $isVertical ? '22px 20px' : '20px 22px' }};
        }

        .trainer-card-institution {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '54px 1fr' }};
            gap: 10px;
            align-items: center;
            text-align: {{ $isVertical ? 'center' : 'left' }};
        }

        .trainer-card-logo {
            width: {{ $isVertical ? '62px' : '54px' }};
            height: {{ $isVertical ? '62px' : '54px' }};
            margin: {{ $isVertical ? '0 auto' : '0' }};
            object-fit: contain;
        }

        .trainer-card-institution-name {
            color: {{ $frontText }};
            font-size: {{ $isVertical ? '10px' : '11px' }};
            font-weight: 900;
            line-height: 1.18;
            text-transform: uppercase;
        }

        .trainer-card-institution-location {
            margin-top: 3px;
            color: {{ $frontText }};
            font-size: 8px;
            font-weight: 700;
            opacity: .72;
        }

        .trainer-card-front-number {
            margin-top: 5px;
            color: {{ $frontText }};
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .trainer-card-front-body {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '1fr 116px' }};
            gap: {{ $isVertical ? '12px' : '16px' }};
            align-items: center;
            flex: 1;
            min-height: 0;
        }

        .trainer-card-info {
            display: grid;
            gap: {{ $isVertical ? '5px' : '6px' }};
            min-width: 0;
            color: {{ $frontText }};
            font-size: {{ $isVertical ? '10px' : '11px' }};
            line-height: 1.2;
        }

        .trainer-card-name {
            color: {{ $template->secondary_color ?: '#dc2626' }};
            font-size: {{ $isVertical ? '14px' : '16px' }};
            font-weight: 950;
            line-height: 1.08;
            text-transform: uppercase;
        }

        .trainer-card-info strong {
            font-weight: 950;
            text-transform: uppercase;
        }

        .trainer-card-photo-wrap {
            display: grid;
            justify-items: center;
            gap: 7px;
        }

        .trainer-card-photo {
            width: {{ $isVertical ? '106px' : '104px' }};
            height: {{ $isVertical ? '128px' : '122px' }};
            border: 3px solid var(--trainer-card-primary);
            border-radius: 12px;
            background: #e5e7eb;
            object-fit: cover;
        }

        .trainer-card-photo-initials {
            display: grid;
            place-items: center;
            color: var(--trainer-card-primary);
            font-size: 28px;
            font-weight: 950;
        }

        .trainer-card-regime {
            padding: 5px 9px;
            border-radius: 999px;
            background: var(--trainer-card-primary);
            color: #fff;
            font-size: 9px;
            font-weight: 950;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .trainer-card-back-title {
            color: {{ $backText }};
            font-size: 14px;
            font-weight: 950;
            text-align: center;
            text-transform: uppercase;
        }

        .trainer-card-back-grid {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '1.2fr .8fr' }};
            gap: 14px;
            align-items: start;
            margin-top: 18px;
            color: {{ $backText }};
            font-size: 10px;
            line-height: 1.35;
        }

        .trainer-card-back-list {
            display: grid;
            gap: 6px;
        }

        .trainer-card-back-list strong {
            font-weight: 950;
            text-transform: uppercase;
        }

        .trainer-card-qr-wrap {
            display: grid;
            justify-items: center;
            gap: 8px;
        }

        .trainer-card-qr {
            width: {{ $isVertical ? '72px' : '74px' }};
            height: {{ $isVertical ? '72px' : '74px' }};
            object-fit: contain;
            border: 6px solid #fff;
            border-radius: 10px;
            background: #fff;
        }

        .trainer-card-qr-fallback {
            width: {{ $isVertical ? '72px' : '74px' }};
            height: {{ $isVertical ? '72px' : '74px' }};
            border: 6px solid #fff;
            border-radius: 10px;
            background:
                linear-gradient(90deg, #111827 8px, transparent 8px) 0 0 / 18px 18px,
                linear-gradient(#111827 8px, transparent 8px) 0 0 / 18px 18px,
                #fff;
        }

        .trainer-card-footer-text {
            margin-top: auto;
            padding-top: 14px;
            color: {{ $backText }};
            font-size: 9px;
            line-height: 1.35;
            text-align: center;
            white-space: pre-line;
        }

        .trainer-card-signature {
            display: grid;
            justify-items: center;
            gap: 3px;
            margin-top: auto;
            color: {{ $backText }};
            font-size: 9px;
            text-align: center;
        }

        .trainer-card-signature img {
            width: 112px;
            height: 42px;
            object-fit: contain;
        }

        .trainer-card-bottom {
            display: grid;
            justify-items: center;
            gap: 9px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
        }

        .trainer-card-side-labels {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .trainer-card-side-labels span.active {
            color: #0f172a;
        }

        .trainer-card-flip-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 34px;
            padding: 8px 16px;
            border: 0;
            border-radius: 8px;
            background: #1e293b;
            color: #fff;
            font-size: 11px;
            font-weight: 950;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .18s ease;
        }

        .trainer-card-flip-button:hover {
            background: #071a44;
        }

        .sigef-trainer-card-expanded {
            width: 92vw !important;
            max-width: 92vw !important;
        }

        .sigef-trainer-card-expanded .trainer-card-stage {
            min-height: 68vh;
        }

        @media (max-width: 900px) {
            .trainer-card-toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px;
            }

            .trainer-card-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .trainer-card-stage {
                min-height: 420px;
                padding: 24px 10px;
            }

            .sigef-trainer-card-modal {
                --trainer-card-screen-width: min({{ $screenWidth }}, 88vw);
                --trainer-card-screen-height: calc(var(--trainer-card-screen-width) * {{ $isVertical ? '1.584' : '.632' }});
            }
        }

        @media print {
            @page {
                size: {{ $printWidth }} {{ $printHeight }};
                margin: 0;
            }

            body * {
                visibility: hidden !important;
            }

            .sigef-trainer-card-modal,
            .sigef-trainer-card-modal * {
                visibility: visible !important;
            }

            .sigef-trainer-card-modal {
                position: fixed !important;
                inset: 0 !important;
                display: block !important;
                width: var(--trainer-card-print-width) !important;
                height: var(--trainer-card-print-height) !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .trainer-card-toolbar,
            .trainer-card-bottom {
                display: none !important;
            }

            .trainer-card-stage {
                display: block !important;
                width: var(--trainer-card-print-width) !important;
                height: var(--trainer-card-print-height) !important;
                min-height: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                overflow: visible !important;
            }

            .trainer-card-scene,
            .trainer-card-stack {
                width: var(--trainer-card-print-width) !important;
                height: var(--trainer-card-print-height) !important;
                perspective: none !important;
            }

            .trainer-card-face {
                width: var(--trainer-card-print-width) !important;
                height: var(--trainer-card-print-height) !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            .trainer-card-face:not(.is-active) {
                display: none !important;
            }
        }
    </style>

    <div class="trainer-card-toolbar">
        <div class="trainer-card-title-wrap">
            <div class="trainer-card-title">
                <x-filament::icon icon="heroicon-o-identification" class="trainer-card-toolbar-icon" />
                <span>Formadores - {{ $payload['name'] }}</span>
            </div>

            <div class="trainer-card-badges">
                <span class="trainer-card-badge primary">{{ $orientationLabel }}</span>
                <span class="trainer-card-badge muted" x-text="sideLabel">Frente</span>
                <span class="trainer-card-badge {{ $payload['is_active'] ? 'success' : 'danger' }}">
                    {{ $payload['is_active'] ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>

        <div class="trainer-card-actions">
            <button type="button" class="trainer-card-button" x-on:click.prevent.stop="printCard()">
                <x-filament::icon icon="heroicon-o-printer" class="trainer-card-button-icon" />
                <span>Imprimir</span>
            </button>

            <button type="button" class="trainer-card-button accent" x-on:click.prevent.stop="toggleExpand()">
                <span x-show="!isExpanded">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="trainer-card-button-icon" />
                </span>
                <span x-show="isExpanded" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="trainer-card-button-icon" />
                </span>
                <span x-text="isExpanded ? 'Reduzir' : 'Expandir'">Expandir</span>
            </button>
        </div>
    </div>

    <div class="trainer-card-stage">
        <div class="trainer-card-scene">
            <div class="trainer-card-stack">
                <section
                    class="trainer-card-face trainer-card-front"
                    x-bind:class="{ 'is-active': side === 'front' }"
                    style="{{ $frontStyle }} color: {{ $frontText }};"
                >
                    <header class="trainer-card-institution">
                        <img class="trainer-card-logo" src="{{ $payload['logo_url'] }}" alt="Logo">
                        <div>
                            <div class="trainer-card-institution-name">{{ $payload['institution_name'] }}</div>
                            @if ($payload['institution_location'])
                                <div class="trainer-card-institution-location">{{ $payload['institution_location'] }}</div>
                            @endif
                            <div class="trainer-card-front-number">
                                {{ $payload['front_title'] }} - {{ $payload['document_label'] }} {{ $payload['document_number'] }}
                            </div>
                        </div>
                    </header>

                    <div class="trainer-card-front-body">
                        <div class="trainer-card-info">
                            <div><strong>Nome:</strong></div>
                            <div class="trainer-card-name">{{ $payload['name'] }}</div>
                            <div><strong>{{ $payload['document_label'] }}:</strong> {{ $payload['document_number'] }}</div>
                            <div><strong>Funcao:</strong> {{ $payload['function'] }}</div>
                            <div><strong>Departamento:</strong> {{ $payload['department'] }}</div>
                            @if ($payload['discipline'] !== '-')
                                <div><strong>Disciplina:</strong> {{ $payload['discipline'] }}</div>
                            @endif

                        </div>

                        <div class="trainer-card-photo-wrap">
                            @if ($payload['photo_url'])
                                <img class="trainer-card-photo" src="{{ $payload['photo_url'] }}" alt="Foto de {{ $payload['name'] }}">
                            @else
                                <div class="trainer-card-photo trainer-card-photo-initials">{{ $payload['initials'] ?: 'FR' }}</div>
                            @endif
                            <div class="trainer-card-regime">{{ $payload['regime'] }}</div>
                        </div>
                    </div>
                </section>

                <section
                    class="trainer-card-face trainer-card-back"
                    x-bind:class="{ 'is-active': side === 'back' }"
                    style="{{ $backStyle }}"
                >
                    <div class="trainer-card-back-title">{{ $template->back_title ?: 'Identificacao do Formador' }}</div>

                    <div class="trainer-card-back-grid">
                        <div class="trainer-card-back-list">
                            <div><strong>{{ $payload['document_label'] }}:</strong> {{ $payload['document_number'] }}</div>
                            <div><strong>Regime:</strong> {{ $payload['regime'] }}</div>
                            <div><strong>Patente:</strong> {{ $payload['rank'] }}</div>
                            <div><strong>Orgao:</strong> {{ $payload['organ'] }}</div>
                            <div><strong>Telefone:</strong> {{ $payload['phone'] }}</div>
                            <div><strong>E-mail:</strong> {{ $payload['email'] }}</div>
                            <div><strong>Disciplinas:</strong> {{ $payload['subjects'] }}</div>
                            <div><strong>Turmas:</strong> {{ $payload['classes'] }}</div>
                        </div>

                        <div class="trainer-card-qr-wrap">
                            @if ($payload['show_qr_code'] && $payload['qr_code_uri'])
                                <img class="trainer-card-qr" src="{{ $payload['qr_code_uri'] }}" alt="QR code">
                            @elseif ($payload['show_qr_code'])
                                <div class="trainer-card-qr-fallback" aria-hidden="true"></div>
                            @endif

                            <div class="trainer-card-signature">
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
                        </div>
                    </div>

                    <div class="trainer-card-footer-text">{{ $payload['footer_text'] }}</div>
                </section>
            </div>
        </div>
    </div>

    <div class="trainer-card-bottom">
        <div class="trainer-card-side-switch">
            <div class="trainer-card-side-labels">
                <span x-bind:class="{ 'active': side === 'front' }">Frente</span>
                <button type="button" class="trainer-card-flip-button" x-on:click.prevent.stop="toggleSide()">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="trainer-card-button-icon" />
                    <span>Virar Cartao</span>
                </button>
                <span x-bind:class="{ 'active': side === 'back' }">Verso</span>
            </div>
        </div>
        <div>Pre-visualize frente e verso do cartao antes de imprimir.</div>
    </div>
</div>
