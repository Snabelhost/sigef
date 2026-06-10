@php
    $documentType = $documentType ?? 'ficha';
    $showOrientationSelector = $showOrientationSelector ?? true;
    $loadingText = $loadingText ?? 'A preparar '.$documentType.'...';
    $hintText = $hintText ?? (
        $showOrientationSelector
            ? 'Pre-visualize a '.$documentType.' em A4 e escolha a orientacao antes de imprimir.'
            : 'Pre-visualize o '.$documentType.' em A4 antes de imprimir.'
    );
@endphp

<div class="document-preview-modal-wrapper"
     id="{{ $viewerId }}"
     x-bind:class="'dpv-orientation-' + orientation"
     x-data="{
        isExpanded: false,
        isPrinting: false,
        isSwitching: false,
        orientation: @js($defaultOrientation),
        embeddedUrls: {
            horizontal: @js($embeddedHorizontalUrl),
            vertical: @js($embeddedVerticalUrl),
        },
        printUrls: {
            horizontal: @js($fallbackPrintHorizontalUrl),
            vertical: @js($fallbackPrintVerticalUrl),
        },
        orientationLabels: {
            horizontal: 'Horizontal',
            vertical: 'Vertical',
        },
        get activeEmbeddedUrl() {
            return this.embeddedUrls[this.orientation] ?? this.embeddedUrls.horizontal;
        },
        get activePrintUrl() {
            return this.printUrls[this.orientation] ?? this.printUrls.horizontal;
        },
        setOrientation(value) {
            if (! this.embeddedUrls[value] || this.orientation === value) {
                return;
            }

            this.isSwitching = true;
            this.orientation = value;
        },
        orientationLabel() {
            return this.orientationLabels[this.orientation] ?? this.orientationLabels.horizontal;
        },
        toggleExpand() {
            this.isExpanded = ! this.isExpanded;

            let el = this.$el;

            while (el && el !== document.body) {
                el = el.parentElement;

                if (el && el.className && el.className.toString().indexOf('fi-modal-window') >= 0) {
                    el.classList.toggle('dpv-expanded', this.isExpanded);
                    break;
                }
            }
        },
        printDocument() {
            if (this.isPrinting) {
                return;
            }

            this.isPrinting = true;

            const previewFrame = document.getElementById(@js($frameId));
            const fallbackUrl = this.activePrintUrl;

            try {
                if (previewFrame && previewFrame.contentWindow) {
                    previewFrame.contentWindow.focus();
                    previewFrame.contentWindow.print();
                    setTimeout(() => { this.isPrinting = false; }, 900);
                    return;
                }
            } catch (error) {}

            window.open(fallbackUrl, '_blank');
            setTimeout(() => { this.isPrinting = false; }, 900);
        },
        frameLoaded() {
            this.isSwitching = false;
        }
     }">
    <style>
        .document-preview-modal-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .dpv-header {
            padding: 12px 20px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #f1f5f9;
            border-radius: 10px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .dpv-title-wrap {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-direction: column;
            gap: 10px;
        }

        .dpv-title {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex-wrap: wrap;
            font-size: .78rem;
            font-weight: 600;
        }

        .dpv-title-icon,
        .dpv-btn-icon,
        .dpv-segment-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .dpv-title-icon {
            opacity: .7;
        }

        .dpv-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .dpv-badge {
            font-size: .55rem;
            padding: 3px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 700;
        }

        .dpv-badge-primary {
            background: rgba(59, 130, 246, .85);
            color: #fff;
        }

        .dpv-badge-muted {
            background: rgba(148, 163, 184, .18);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .dpv-actions {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .dpv-orientation {
            display: inline-flex;
            align-items: center;
            padding: 3px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .dpv-segment {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s ease;
        }

        .dpv-segment:disabled {
            opacity: .65;
            cursor: wait;
        }

        .dpv-segment:hover {
            color: #f8fafc;
        }

        .dpv-segment.active {
            background: rgba(59, 130, 246, .92);
            color: #fff;
            box-shadow: 0 4px 10px rgba(59, 130, 246, .24);
        }

        .dpv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            white-space: nowrap;
            transition: all .2s ease;
            cursor: pointer;
            letter-spacing: .04em;
            border: 0;
            line-height: 1;
        }

        .dpv-btn-outline {
            background: rgba(255, 255, 255, .08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .dpv-btn-outline:hover {
            background: rgba(255, 255, 255, .15);
            color: #f1f5f9;
        }

        .dpv-btn-accent {
            background: #3b82f6;
            color: #fff;
        }

        .dpv-btn-accent:hover {
            background: #2563eb;
            box-shadow: 0 2px 8px rgba(59, 130, 246, .35);
        }

        .dpv-btn:disabled {
            opacity: .6;
            cursor: wait;
        }

        @keyframes dpv-spin {
            to { transform: rotate(360deg); }
        }

        .dpv-spin {
            animation: dpv-spin .8s linear infinite;
        }

        .dpv-stage {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            min-height: 520px;
        }

        .dpv-frame-shell {
            position: relative;
            flex: 1;
            width: 100%;
            min-height: 74vh;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            background: #fff;
            box-shadow: 0 10px 40px rgba(15, 23, 42, .08);
        }

        .dpv-frame-shell iframe {
            width: 100%;
            height: 74vh;
            border: 0;
            display: block;
            background: #fff;
        }

        .dpv-loading {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2;
            background: rgba(248, 250, 252, .82);
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .dpv-loading.active {
            display: flex;
        }

        .dpv-hint {
            padding: 10px 4px 0;
            text-align: center;
            color: #64748b;
            font-size: 11px;
            letter-spacing: .02em;
        }

        .dpv-expanded {
            max-width: 96vw !important;
            width: 96vw !important;
            margin-left: auto !important;
            margin-right: auto !important;
            transition: max-width .3s ease, width .3s ease;
        }

        .dpv-expanded .dpv-frame-shell,
        .dpv-expanded .dpv-frame-shell iframe {
            height: 78vh;
        }

        @media (max-width: 1100px) {
            .dpv-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 14px;
            }

            .dpv-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 900px) {
            .dpv-orientation {
                width: 100%;
                justify-content: stretch;
            }

            .dpv-segment {
                flex: 1;
                justify-content: center;
            }

            .dpv-stage {
                min-height: 320px;
                padding: 12px;
            }

            .dpv-frame-shell,
            .dpv-frame-shell iframe {
                min-height: 64vh;
                height: 64vh;
            }
        }
    </style>

    <div class="dpv-header">
        <div class="dpv-title-wrap">
            <div class="dpv-title">
                <x-filament::icon icon="heroicon-o-document-text" class="dpv-title-icon" />
                <span>{{ $documentName }}</span>
            </div>

            <div class="dpv-badge-row">
                <span class="dpv-badge dpv-badge-primary">{!! $documentBadge !!}</span>
                <span class="dpv-badge dpv-badge-muted" x-text="orientationLabel()">Horizontal</span>
            </div>
        </div>

        <div class="dpv-actions">
            @if ($showOrientationSelector)
            <div class="dpv-orientation" role="group" aria-label="Orientacao da ficha">
                <button type="button"
                        class="dpv-segment"
                        x-bind:class="{ 'active': orientation === 'horizontal' }"
                        x-bind:disabled="isSwitching"
                        x-on:click.prevent.stop="setOrientation('horizontal')">
                    <x-filament::icon icon="heroicon-o-view-columns" class="dpv-segment-icon" />
                    <span>Horizontal</span>
                </button>

                <button type="button"
                        class="dpv-segment"
                        x-bind:class="{ 'active': orientation === 'vertical' }"
                        x-bind:disabled="isSwitching"
                        x-on:click.prevent.stop="setOrientation('vertical')">
                    <x-filament::icon icon="heroicon-o-rectangle-stack" class="dpv-segment-icon" />
                    <span>Vertical</span>
                </button>
            </div>
            @endif

            <button type="button" class="dpv-btn dpv-btn-outline" x-on:click.prevent.stop="printDocument()" x-bind:disabled="isPrinting">
                <span x-show="!isPrinting">
                    <x-filament::icon icon="heroicon-o-printer" class="dpv-btn-icon" />
                </span>
                <span x-show="isPrinting" class="dpv-spin" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="dpv-btn-icon" />
                </span>
                <span x-text="isPrinting ? 'A preparar...' : 'Imprimir'">Imprimir</span>
            </button>

            <button type="button" class="dpv-btn dpv-btn-accent" x-on:click.prevent.stop="toggleExpand()">
                <span x-show="!isExpanded">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="dpv-btn-icon" />
                </span>
                <span x-show="isExpanded" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="dpv-btn-icon" />
                </span>
                <span x-text="isExpanded ? 'Reduzir' : 'Expandir'">Expandir</span>
            </button>
        </div>
    </div>

    <div class="dpv-stage">
        <div class="dpv-frame-shell">
            <div class="dpv-loading" x-bind:class="{ 'active': isSwitching }">{{ $loadingText }}</div>
            <iframe
                id="{{ $frameId }}"
                x-bind:src="activeEmbeddedUrl"
                x-on:load="frameLoaded()"
                title="{{ $documentName }}"
                scrolling="auto"
            ></iframe>
        </div>
        <div class="dpv-hint">{{ $hintText }}</div>
    </div>
</div>
