@php
    use App\Models\CardTemplate;

    $type = $template->card_type ?? CardTemplate::TYPE_STUDENT;
    $orientation = $template->orientation ?: CardTemplate::ORIENTATION_HORIZONTAL;
    $isVertical = $orientation === CardTemplate::ORIENTATION_VERTICAL;
    $viewerId = $viewerId ?? 'sigef-card-preview-'.uniqid();
    $entityLabel = $entityLabel ?? match ($type) {
        CardTemplate::TYPE_STUDENT => 'Formandos',
        CardTemplate::TYPE_PROFESSOR => 'Formadores',
        CardTemplate::TYPE_STAFF => 'Efectivos',
        default => 'Cartoes',
    };
    $entityTitle = $payload['entity_title'] ?? match ($type) {
        CardTemplate::TYPE_STUDENT => 'FORMANDO',
        CardTemplate::TYPE_PROFESSOR => 'FORMADOR',
        CardTemplate::TYPE_STAFF => 'EFECTIVO',
        default => 'SIGEF',
    };
    $documentName = $documentName ?? $entityLabel.' - '.($payload['name'] ?? 'SIGEF');
    $academicYearValue = $payload['academic_year'] ?? ($academicYear ?? null);
    $frontText = $template->front_text_color ?: $template->text_color ?: '#061b42';
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
    $isActive = (bool) ($payload['is_active'] ?? true);
    $statusLabel = $statusLabel ?? ($payload['status_label'] ?? ($isActive ? 'ACTIVO' : 'INACTIVO'));
    $statusTone = $statusColor ?? ($isActive ? 'success' : 'danger');
    $statusClass = in_array($statusTone, ['success', 'danger', 'warning', 'info'], true) ? $statusTone : 'success';
    $name = $payload['name'] ?? 'SIGEF';
    $initials = $payload['initials'] ?? collect(preg_split('/\s+/', trim($name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $documentLabel = $payload['document_label'] ?? ($template->number_label ?: 'Nº');
    $documentNumber = $payload['document_number'] ?? $payload['number'] ?? $payload['card_number'] ?? '-';
    $logoUrl = $payload['logo_url'] ?? $template->logo_url ?? asset('images/logo-policia.png');
    $photoUrl = $payload['photo_url'] ?? null;
    $brandName = $payload['institution_name'] ?? $template->brand_name ?? 'SIGEF';
    $institutionLocation = $payload['institution_location'] ?? $template->address_line ?? null;
    $frontTitle = $payload['front_title'] ?? $template->front_title ?? 'CARTAO DE IDENTIFICACAO';
    $cardNumber = $payload['card_number'] ?? $payload['number'] ?? $documentNumber;
    $showQrCode = (bool) ($payload['show_qr_code'] ?? $template->show_qr_code ?? true);

    $frontRows = collect([
        [$documentLabel, $documentNumber],
        ['Curso', $payload['course'] ?? null],
        ['Turma', $payload['class'] ?? null],
        ['Ano académico', $academicYearValue],
        ['Função', $payload['function'] ?? null],
        ['Departamento', $payload['department'] ?? null],
        ['Disciplina', $payload['discipline'] ?? null],
        ['Posto', $payload['rank'] ?? $payload['position'] ?? null],
        ['Regime', $payload['regime'] ?? null],
        ['Grupo sanguíneo', $payload['blood_type'] ?? null],
        ['Colocação', $payload['placement'] ?? null],
    ])->filter(fn (array $row): bool => filled($row[1] ?? null) && ($row[1] ?? '-') !== '-')->values();

    $backRows = collect([
        [$documentLabel, $documentNumber],
        ['Regime', $payload['regime'] ?? null],
        ['Posto', $payload['rank'] ?? $payload['position'] ?? null],
        ['Órgão', $payload['organ'] ?? $payload['placement_organ'] ?? null],
        ['Departamento', $payload['department'] ?? null],
        ['Função', $payload['function'] ?? null],
        ['Telefone', $payload['phone'] ?? null],
        ['E-mail', $payload['email'] ?? null],
        ['Disciplinas', $payload['subjects'] ?? null],
        ['Turmas', $payload['classes'] ?? null],
        ['Grupo sanguíneo', $payload['blood_type'] ?? null],
        ['Validação', $payload['verification_url'] ?? null],
    ])->filter(fn (array $row): bool => filled($row[1] ?? null) && ($row[1] ?? '-') !== '-')->values();
@endphp

<div
    id="{{ $viewerId }}"
    class="sigef-card-preview-modal {{ $isVertical ? 'is-vertical' : 'is-horizontal' }}"
    x-data="{
        side: 'front',
        isExpanded: false,
        get sideLabel() {
            return this.side === 'front' ? 'Frente' : 'Verso';
        },
        toggleSide() {
            this.side = this.side === 'front' ? 'back' : 'front';
        },
        showFront() {
            this.side = 'front';
        },
        showBack() {
            this.side = 'back';
        },
        toggleExpand() {
            this.isExpanded = ! this.isExpanded;

            let el = this.$el;

            while (el && el !== document.body) {
                el = el.parentElement;

                if (el && el.className && el.className.toString().indexOf('fi-modal-window') >= 0) {
                    el.classList.toggle('sigef-card-preview-expanded', this.isExpanded);
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
        @font-face {
            font-family: "Montserrat SIGEF";
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url("{{ asset('fonts/montserrat/Montserrat-Bold.woff2') }}") format("woff2");
        }

        @font-face {
            font-family: "Montserrat SIGEF";
            font-style: normal;
            font-weight: 800;
            font-display: swap;
            src: url("{{ asset('fonts/montserrat/Montserrat-ExtraBold.woff2') }}") format("woff2");
        }

        @font-face {
            font-family: "Montserrat SIGEF";
            font-style: normal;
            font-weight: 900;
            font-display: swap;
            src: url("{{ asset('fonts/montserrat/Montserrat-Black.woff2') }}") format("woff2");
        }

        .sigef-card-preview-modal {
            --sigef-card-screen-width: {{ $screenWidth }};
            --sigef-card-screen-height: {{ $screenHeight }};
            --sigef-card-print-width: {{ $printWidth }};
            --sigef-card-print-height: {{ $printHeight }};
            --sigef-card-primary: {{ $template->primary_color ?: '#061b42' }};
            --sigef-card-secondary: {{ $template->secondary_color ?: '#2563eb' }};
            display: grid;
            gap: 16px;
            color: #0f172a;
            font-family: "Montserrat SIGEF", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .sigef-card-preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e293b 0%, #061b42 100%);
            color: #f8fafc;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
        }

        .sigef-card-preview-title-wrap {
            display: grid;
            gap: 9px;
            min-width: 0;
        }

        .sigef-card-preview-title {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            font-size: 13px;
            font-weight: 800;
        }

        .sigef-card-preview-title span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-card-preview-toolbar-icon,
        .sigef-card-preview-button-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .sigef-card-preview-badges,
        .sigef-card-preview-actions,
        .sigef-card-preview-side-switch {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sigef-card-preview-badge {
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

        .sigef-card-preview-badge.primary {
            background: #2563eb;
            color: #fff;
        }

        .sigef-card-preview-badge.muted {
            background: rgba(255, 255, 255, .12);
            color: #dbeafe;
            border: 1px solid rgba(255, 255, 255, .14);
        }

        .sigef-card-preview-badge.success {
            background: #16a34a;
            color: #fff;
        }

        .sigef-card-preview-badge.danger {
            background: #dc2626;
            color: #fff;
        }

        .sigef-card-preview-badge.warning {
            background: #f59e0b;
            color: #111827;
        }

        .sigef-card-preview-badge.info {
            background: #0ea5e9;
            color: #fff;
        }

        .sigef-card-preview-button {
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

        .sigef-card-preview-button:hover {
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .sigef-card-preview-button.accent {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }

        .sigef-card-preview-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 560px;
            padding: 28px 18px 24px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef4ff 100%);
            overflow: hidden;
        }

        .sigef-card-preview-scene {
            position: relative;
            width: var(--sigef-card-screen-width);
            height: var(--sigef-card-screen-height);
            max-width: 100%;
            perspective: 1200px;
        }

        .sigef-card-preview-stack {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transform-origin: center;
            transition: transform .7s cubic-bezier(.4, 0, .2, 1);
        }

        .sigef-card-preview-stack.is-flipped {
            transform: rotateY(180deg);
        }

        .sigef-card-preview-face {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 12px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 24px 50px rgba(15, 23, 42, .18);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .sigef-card-preview-back {
            transform: rotateY(180deg);
        }

        .sigef-card-preview-front {
            padding: {{ $isVertical ? '18px 18px 16px' : '18px 20px' }};
        }

        .sigef-card-preview-back {
            padding: {{ $isVertical ? '22px 20px' : '20px 22px' }};
        }

        .sigef-card-preview-watermark {
            position: absolute;
            inset: 64px 108px 42px 112px;
            opacity: .14;
            object-fit: contain;
            pointer-events: none;
        }

        .sigef-card-preview-institution {
            position: relative;
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '54px 1fr' }};
            gap: 10px;
            align-items: center;
            text-align: {{ $isVertical ? 'center' : 'left' }};
            z-index: 1;
        }

        .sigef-card-preview-logo {
            width: {{ $isVertical ? '62px' : '54px' }};
            height: {{ $isVertical ? '62px' : '54px' }};
            margin: {{ $isVertical ? '0 auto' : '0' }};
            object-fit: contain;
        }

        .sigef-card-preview-institution-name {
            color: {{ $frontText }};
            font-size: {{ $isVertical ? '10px' : '11px' }};
            font-weight: 950;
            line-height: 1.18;
            text-transform: uppercase;
        }

        .sigef-card-preview-location {
            margin-top: 3px;
            color: {{ $frontText }};
            font-size: 8px;
            font-weight: 700;
            opacity: .72;
        }

        .sigef-card-preview-front-number {
            margin-top: 5px;
            color: {{ $frontText }};
            font-size: 9px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .sigef-card-preview-front-body {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '92px 1fr 118px' }};
            gap: {{ $isVertical ? '10px' : '14px' }};
            align-items: center;
            flex: 1;
            min-height: 0;
        }

        .sigef-card-preview-entity-mark {
            grid-area: entity;
            color: {{ $template->secondary_color ?: '#dc2626' }};
            font-family: Georgia, "Times New Roman", serif;
            font-size: {{ $isVertical ? '24px' : '34px' }};
            font-weight: 900;
            line-height: 1;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-card-preview-info {
            grid-area: info;
            display: grid;
            gap: {{ $isVertical ? '5px' : '4px' }};
            min-width: 0;
            color: {{ $frontText }};
            font-size: {{ $isVertical ? '10px' : '11px' }};
            line-height: 1.18;
        }

        .sigef-card-preview-name {
            color: {{ $template->secondary_color ?: '#ef233c' }};
            font-size: {{ $isVertical ? '14px' : '16px' }};
            font-weight: 950;
            line-height: 1.08;
            text-transform: uppercase;
        }

        .sigef-card-preview-info strong {
            font-weight: 950;
            text-transform: uppercase;
        }

        .sigef-card-preview-row {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-card-preview-photo-wrap {
            grid-area: photo;
            display: grid;
            justify-items: center;
            gap: 7px;
        }

        .sigef-card-preview-photo {
            width: {{ $isVertical ? '106px' : '104px' }};
            height: {{ $isVertical ? '128px' : '122px' }};
            display: grid;
            place-items: center;
            border: 3px solid var(--sigef-card-primary);
            border-radius: 14px;
            background: #e5e7eb;
            color: var(--sigef-card-primary);
            font-size: 28px;
            font-weight: 950;
            object-fit: cover;
        }

        .sigef-card-preview-pill {
            max-width: 112px;
            padding: 5px 9px;
            border-radius: 999px;
            background: var(--sigef-card-primary);
            color: #fff;
            font-size: 9px;
            font-weight: 950;
            line-height: 1.1;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-card-preview-back-title {
            color: {{ $backText }};
            font-size: 14px;
            font-weight: 950;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-card-preview-back-grid {
            display: grid;
            grid-template-columns: {{ $isVertical ? '1fr' : '1.18fr .82fr' }};
            gap: 14px;
            align-items: start;
            margin-top: 18px;
            color: {{ $backText }};
            font-size: 10px;
            line-height: 1.35;
        }

        .sigef-card-preview-back-list {
            display: grid;
            gap: 6px;
            min-width: 0;
        }

        .sigef-card-preview-back-list strong {
            font-weight: 950;
            text-transform: uppercase;
        }

        .sigef-card-preview-back-list div {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-card-preview-qr-wrap {
            display: grid;
            justify-items: center;
            gap: 8px;
        }

        .sigef-card-preview-qr {
            width: {{ $isVertical ? '72px' : '74px' }};
            height: {{ $isVertical ? '72px' : '74px' }};
            object-fit: contain;
            border: 6px solid #fff;
            border-radius: 10px;
            background: #fff;
        }

        .sigef-card-preview-qr-fallback {
            width: {{ $isVertical ? '72px' : '74px' }};
            height: {{ $isVertical ? '72px' : '74px' }};
            border: 6px solid #fff;
            border-radius: 10px;
            background:
                linear-gradient(90deg, #111827 8px, transparent 8px) 0 0 / 18px 18px,
                linear-gradient(#111827 8px, transparent 8px) 0 0 / 18px 18px,
                #fff;
        }

        .sigef-card-preview-footer-text {
            margin-top: auto;
            padding-top: 14px;
            color: {{ $backText }};
            font-size: 9px;
            line-height: 1.35;
            text-align: center;
            white-space: pre-line;
        }

        .sigef-card-preview-signature {
            display: grid;
            justify-items: center;
            gap: 3px;
            margin-top: auto;
            color: {{ $backText }};
            font-size: 9px;
            text-align: center;
        }

        .sigef-card-preview-signature img {
            width: 112px;
            height: 42px;
            object-fit: contain;
        }

        .sigef-card-preview-bottom {
            display: grid;
            justify-items: center;
            gap: 9px;
            margin-top: 16px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
        }

        .sigef-card-preview-side-labels {
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

        .sigef-card-preview-side-labels span {
            cursor: pointer;
        }

        .sigef-card-preview-side-labels span.active {
            color: #0f172a;
        }

        .sigef-card-preview-flip-button {
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

        .sigef-card-preview-flip-button:hover {
            background: #061b42;
        }

        .sigef-card-preview-expanded {
            width: 92vw !important;
            max-width: 92vw !important;
        }

        .sigef-card-preview-expanded .sigef-card-preview-stage {
            min-height: 68vh;
        }

        .sigef-card-preview-modal.is-vertical .sigef-card-preview-front-body {
            grid-template-areas:
                "photo"
                "entity"
                "info";
            align-content: center;
        }

        .sigef-card-preview-modal.is-horizontal .sigef-card-preview-front-body {
            grid-template-areas: "entity info photo";
        }

        @media (max-width: 900px) {
            .sigef-card-preview-toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px;
            }

            .sigef-card-preview-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .sigef-card-preview-stage {
                min-height: 430px;
                padding: 24px 10px;
            }

            .sigef-card-preview-modal {
                --sigef-card-screen-width: min({{ $screenWidth }}, 88vw);
                --sigef-card-screen-height: calc(var(--sigef-card-screen-width) * {{ $isVertical ? '1.584' : '.632' }});
            }

            .sigef-card-preview-scene {
                width: var(--sigef-card-screen-width);
                height: var(--sigef-card-screen-height);
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

            .sigef-card-preview-modal,
            .sigef-card-preview-modal * {
                visibility: visible !important;
            }

            .sigef-card-preview-modal {
                position: fixed !important;
                inset: 0 !important;
                display: block !important;
                width: var(--sigef-card-print-width) !important;
                height: var(--sigef-card-print-height) !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .sigef-card-preview-toolbar,
            .sigef-card-preview-bottom {
                display: none !important;
            }

            .sigef-card-preview-stage {
                display: block !important;
                width: var(--sigef-card-print-width) !important;
                height: var(--sigef-card-print-height) !important;
                min-height: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                overflow: visible !important;
            }

            .sigef-card-preview-scene,
            .sigef-card-preview-stack {
                width: var(--sigef-card-print-width) !important;
                height: var(--sigef-card-print-height) !important;
                perspective: none !important;
                transform: none !important;
            }

            .sigef-card-preview-face {
                position: relative !important;
                inset: auto !important;
                width: var(--sigef-card-print-width) !important;
                height: var(--sigef-card-print-height) !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            .sigef-card-preview-face:not(.is-active) {
                display: none !important;
            }
        }
    </style>

    <div class="sigef-card-preview-toolbar">
        <div class="sigef-card-preview-title-wrap">
            <div class="sigef-card-preview-title">
                <x-filament::icon icon="heroicon-o-identification" class="sigef-card-preview-toolbar-icon" />
                <span>{{ $documentName }}</span>
            </div>

            <div class="sigef-card-preview-badges">
                <span class="sigef-card-preview-badge primary">{{ $orientationLabel }}</span>
                <span class="sigef-card-preview-badge muted" x-text="sideLabel">Frente</span>
                <span class="sigef-card-preview-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="sigef-card-preview-actions">
            <button type="button" class="sigef-card-preview-button" x-on:click.prevent.stop="printCard()">
                <x-filament::icon icon="heroicon-o-printer" class="sigef-card-preview-button-icon" />
                <span>Imprimir</span>
            </button>

            <button type="button" class="sigef-card-preview-button accent" x-on:click.prevent.stop="toggleExpand()">
                <span x-show="!isExpanded">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="sigef-card-preview-button-icon" />
                </span>
                <span x-show="isExpanded" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="sigef-card-preview-button-icon" />
                </span>
                <span x-text="isExpanded ? 'Reduzir' : 'Expandir'">Expandir</span>
            </button>
        </div>
    </div>

    <div class="sigef-card-preview-stage">
        <div class="sigef-card-preview-scene">
            <div class="sigef-card-preview-stack" x-bind:class="{ 'is-flipped': side === 'back' }">
                <section
                    class="sigef-card-preview-face sigef-card-preview-front"
                    x-bind:class="{ 'is-active': side === 'front' }"
                    style="{{ $frontStyle }} color: {{ $frontText }};"
                >
                    @if ($logoUrl)
                        <img class="sigef-card-preview-watermark" src="{{ $logoUrl }}" alt="">
                    @endif

                    <header class="sigef-card-preview-institution">
                        @if ($logoUrl)
                            <img class="sigef-card-preview-logo" src="{{ $logoUrl }}" alt="Logo">
                        @endif
                        <div>
                            <div class="sigef-card-preview-institution-name">{{ $brandName }}</div>
                            @if ($institutionLocation)
                                <div class="sigef-card-preview-location">{{ $institutionLocation }}</div>
                            @endif
                            <div class="sigef-card-preview-front-number">
                                {{ $frontTitle }} {{ $template->number_label ?: 'Nº' }} {{ $cardNumber }}
                            </div>
                        </div>
                    </header>

                    <div class="sigef-card-preview-front-body">
                        <div class="sigef-card-preview-entity-mark">{{ $entityTitle }}</div>

                        <div class="sigef-card-preview-info">
                            <div><strong>Nome:</strong></div>
                            <div class="sigef-card-preview-name">{{ $name }}</div>

                            @foreach ($frontRows->take($isVertical ? 8 : 7) as [$label, $value])
                                <div class="sigef-card-preview-row"><strong>{{ $label }}:</strong> {{ $value }}</div>
                            @endforeach
                        </div>

                        <div class="sigef-card-preview-photo-wrap">
                            @if ($photoUrl)
                                <img class="sigef-card-preview-photo" src="{{ $photoUrl }}" alt="Foto de {{ $name }}">
                            @else
                                <div class="sigef-card-preview-photo">{{ $initials ?: 'SG' }}</div>
                            @endif
                            <div class="sigef-card-preview-pill">{{ $payload['regime'] ?? $entityTitle }}</div>
                        </div>
                    </div>
                </section>

                <section
                    class="sigef-card-preview-face sigef-card-preview-back"
                    x-bind:class="{ 'is-active': side === 'back' }"
                    style="{{ $backStyle }}"
                >
                    <div class="sigef-card-preview-back-title">
                        {{ $template->back_title ?: 'Identificação do Cartão' }}
                    </div>

                    <div class="sigef-card-preview-back-grid">
                        <div class="sigef-card-preview-back-list">
                            @foreach ($backRows->take($isVertical ? 10 : 9) as [$label, $value])
                                <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                            @endforeach
                        </div>

                        <div class="sigef-card-preview-qr-wrap">
                            @if ($showQrCode && ! empty($payload['qr_code_uri']))
                                <img class="sigef-card-preview-qr" src="{{ $payload['qr_code_uri'] }}" alt="QR code">
                            @elseif ($showQrCode)
                                <div class="sigef-card-preview-qr-fallback" aria-hidden="true"></div>
                            @endif

                            <div class="sigef-card-preview-signature">
                                @if (! empty($payload['signature_url']))
                                    <img src="{{ $payload['signature_url'] }}" alt="Assinatura">
                                @endif
                                @if (! empty($payload['signature_label']))
                                    <span>{{ $payload['signature_label'] }}</span>
                                @endif
                                @if (! empty($payload['signatory_name']))
                                    <strong>{{ $payload['signatory_name'] }}</strong>
                                @endif
                                @if (! empty($payload['signatory_title']))
                                    <span>{{ $payload['signatory_title'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="sigef-card-preview-footer-text">
                        {{ $payload['footer_text'] ?? $template->footer_text ?? 'Este cartão identifica o portador no SIGEF.' }}
                    </div>
                </section>
            </div>
        </div>

    <div class="sigef-card-preview-bottom">
        <div class="sigef-card-preview-side-switch">
            <div class="sigef-card-preview-side-labels">
                <span x-bind:class="{ 'active': side === 'front' }" x-on:click.prevent.stop="showFront()">Frente</span>
                <button type="button" class="sigef-card-preview-flip-button" x-on:click.prevent.stop="toggleSide()">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="sigef-card-preview-button-icon" />
                    <span>Virar Cartão</span>
                </button>
                <span x-bind:class="{ 'active': side === 'back' }" x-on:click.prevent.stop="showBack()">Verso</span>
            </div>
        </div>
        <div>Pré-visualize frente e verso do cartão antes de imprimir.</div>
    </div>
    </div>
</div>
