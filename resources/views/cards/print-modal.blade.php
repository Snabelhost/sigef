@php
    use App\Models\CardTemplate;

    $type = $template->card_type ?? CardTemplate::TYPE_STUDENT;
    $templateStyle = $template->style ?: CardTemplate::STYLE_STUDENT;
    $forceHorizontalAccess = in_array($type, [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF], true);
    $usesAccessVertical = $templateStyle === CardTemplate::STYLE_PROFESSOR_VERTICAL && ! $forceHorizontalAccess;
    $orientation = $template->orientation ?: CardTemplate::ORIENTATION_HORIZONTAL;
    $orientation = $forceHorizontalAccess ? CardTemplate::ORIENTATION_HORIZONTAL : ($usesAccessVertical ? CardTemplate::ORIENTATION_VERTICAL : $orientation);
    $isHorizontal = $orientation !== CardTemplate::ORIENTATION_VERTICAL;
    $cardW = $isHorizontal ? 506 : 306;
    $cardH = $isHorizontal ? 320 : 485;
    $previewUrl = $printUrl ?? route('admin.card-templates.preview', ['cardTemplate' => $template]);
    $cacheBuster = (string) ($template->updated_at?->timestamp ?: time());
    $printUrl = $previewUrl;
    $embeddedFrontUrl = $embeddedFrontUrl ?? $previewUrl.'?embedded=1&autoprint=0&face=front&v='.$cacheBuster;
    $embeddedBackUrl = $embeddedBackUrl ?? $previewUrl.'?embedded=1&autoprint=0&face=back&v='.$cacheBuster;
    $frameId = $frameId ?? 'sigef-card-template-frame-'.$template->getKey();
    $documentName = $documentName ?? 'Modelo - '.$template->name;
    $orientationLabel = $orientationLabel ?? ($orientation === CardTemplate::ORIENTATION_VERTICAL ? 'RETRATO' : 'PAISAGEM');
@endphp

<div
    class="sigef-card-print-modal"
    id="{{ $viewerId }}"
    x-bind:class="{ 'is-expanded': isExpanded }"
    x-data="{
        isExpanded: false,
        isFlipped: false,
        isPrinting: false,
        cardWidth: {{ $cardW }},
        cardHeight: {{ $cardH }},
        previewScale: 1,
        resizeObserver: null,
        init() {
            this.$nextTick(() => {
                this.updatePreviewScale();

                if ('ResizeObserver' in window && this.$refs.cardViewport) {
                    this.resizeObserver = new ResizeObserver(() => this.updatePreviewScale());
                    this.resizeObserver.observe(this.$refs.cardViewport);
                }

                window.addEventListener('resize', () => this.updatePreviewScale());
            });
        },
        toggleFlip() {
            this.isFlipped = ! this.isFlipped;
        },
        toggleExpand() {
            this.isExpanded = ! this.isExpanded;

            let el = this.$el;

            while (el && el !== document.body) {
                el = el.parentElement;

                if (el && el.className && el.className.toString().indexOf('fi-modal-window') >= 0) {
                    el.classList.toggle('sigef-card-print-modal-expanded', this.isExpanded);
                    break;
                }
            }

            this.$nextTick(() => {
                this.updatePreviewScale();
                setTimeout(() => this.updatePreviewScale(), 330);
            });
        },
        updatePreviewScale() {
            const viewport = this.$refs.cardViewport;

            if (! viewport) {
                return;
            }

            const availableWidth = Math.max(1, viewport.clientWidth - 20);
            const availableHeight = Math.max(1, viewport.clientHeight - 20);
            const fitScale = Math.min(availableWidth / this.cardWidth, availableHeight / this.cardHeight);
            const maxScale = this.isExpanded ? 1.35 : 1.2;

            this.previewScale = Math.max(0.38, Math.min(fitScale, maxScale));
        },
        scaleShellStyle() {
            return 'width:' + (this.cardWidth * this.previewScale) + 'px;height:' + (this.cardHeight * this.previewScale) + 'px;';
        },
        flipperStyle() {
            return '--sigef-cpv-scale:' + this.previewScale + ';';
        },
        printCard() {
            if (this.isPrinting) {
                return;
            }

            this.isPrinting = true;

            const url = new URL(@js($printUrl), window.location.origin);
            url.searchParams.delete('face');
            url.searchParams.delete('embedded');
            url.searchParams.set('autoprint', '0');
            url.searchParams.set('print_scale', 'large');

            const printUrl = url.toString();
            const frame = document.createElement('iframe');
            const waitForPrintAssets = () => {
                let doc = frame.contentDocument;

                if (! doc) {
                    return Promise.resolve();
                }

                const imagePromises = Array.from(doc.images)
                    .filter((image) => ! image.complete)
                    .map((image) => new Promise((resolve) => {
                        image.addEventListener('load', resolve, { once: true });
                        image.addEventListener('error', resolve, { once: true });
                    }));
                const fontPromise = doc.fonts ? doc.fonts.ready.catch(() => {}) : Promise.resolve();

                return Promise.race([
                    Promise.all([...imagePromises, fontPromise]),
                    new Promise((resolve) => setTimeout(resolve, 1800)),
                ]).then(() => new Promise((resolve) => setTimeout(resolve, 180)));
            };

            frame.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;opacity:0;border:0;pointer-events:none;z-index:-9999;';
            frame.src = printUrl;
            document.body.appendChild(frame);

            frame.addEventListener('load', () => {
                waitForPrintAssets().then(() => {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (error) {
                        window.open(printUrl, '_blank');
                    }

                    const cleanup = () => {
                        frame.remove();
                        this.isPrinting = false;
                    };

                    if (window.matchMedia) {
                        const afterPrint = () => {
                            window.removeEventListener('afterprint', afterPrint);
                            setTimeout(cleanup, 300);
                        };
                        window.addEventListener('afterprint', afterPrint);
                        setTimeout(() => {
                            window.removeEventListener('afterprint', afterPrint);
                            cleanup();
                        }, 60000);
                    } else {
                        setTimeout(cleanup, 3000);
                    }
                });
            });
        }
    }"
>
    <style>
        .sigef-card-print-modal {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .sigef-cpv-header {
            padding: 12px 20px;
            background: linear-gradient(135deg, #1e293b 0%, #061b42 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #f1f5f9;
            border-radius: 10px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
        }

        .sigef-cpv-title-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }

        .sigef-cpv-title {
            font-size: .78rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .sigef-cpv-title span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-cpv-icon,
        .sigef-cpv-btn-icon,
        .sigef-cpv-toggle-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .sigef-cpv-badges,
        .sigef-cpv-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .sigef-cpv-badge {
            font-size: .55rem;
            padding: 4px 10px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 900;
            line-height: 1;
        }

        .sigef-cpv-badge.primary {
            background: #2563eb;
            color: #fff;
        }

        .sigef-cpv-badge.muted {
            background: rgba(148, 163, 184, .18);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e2e8f0;
        }

        .sigef-cpv-badge.info {
            background: #0ea5e9;
            color: #fff;
        }

        .sigef-cpv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 34px;
            padding: 8px 14px;
            border: 0;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .2s ease;
        }

        .sigef-cpv-btn.outline {
            background: rgba(255, 255, 255, .08);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .sigef-cpv-btn.accent {
            background: #3b82f6;
            color: #fff;
        }

        .sigef-cpv-btn:disabled {
            opacity: .65;
            cursor: wait;
        }

        @keyframes sigef-cpv-spin {
            to { transform: rotate(360deg); }
        }

        .sigef-cpv-spin {
            animation: sigef-cpv-spin .8s linear infinite;
        }

        .sigef-cpv-stage {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 18px;
            min-height: 520px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: #f1f6fd;
            overflow: hidden;
        }

        .sigef-cpv-card-viewport {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 56vh;
            min-height: 420px;
            perspective: 1200px;
        }

        .sigef-cpv-scale-shell {
            position: relative;
            flex: 0 0 auto;
            transition: width .28s ease, height .28s ease;
        }

        .sigef-cpv-flipper {
            --sigef-cpv-scale: 1;
            position: absolute;
            top: 50%;
            left: 50%;
            width: {{ $cardW }}px;
            height: {{ $cardH }}px;
            transform: translate(-50%, -50%) scale(var(--sigef-cpv-scale));
            transform-style: preserve-3d;
            transform-origin: center;
            transition: transform .7s cubic-bezier(.4, 0, .2, 1);
        }

        .sigef-cpv-flipper.is-flipped {
            transform: translate(-50%, -50%) scale(var(--sigef-cpv-scale)) rotateY(180deg);
        }

        .sigef-cpv-face {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .sigef-cpv-face.front {
            z-index: 2;
        }

        .sigef-cpv-face.back {
            transform: rotateY(180deg);
        }

        .sigef-cpv-face iframe {
            width: 100%;
            height: 100%;
            border: 0;
            pointer-events: none;
            display: block;
            background: transparent;
        }

        .sigef-cpv-controls {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .sigef-cpv-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border: 0;
            border-radius: 8px;
            background: #1e293b;
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .sigef-cpv-face-label {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .sigef-cpv-face-label.active {
            color: #061b42;
        }

        .sigef-cpv-hint {
            padding-top: 10px;
            color: #64748b;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
        }

        .sigef-card-print-modal-expanded {
            width: 96vw !important;
            max-width: 96vw !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .sigef-card-print-modal-expanded .sigef-cpv-card-viewport {
            height: 66vh;
        }

        @media (max-width: 900px) {
            .sigef-cpv-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 12px 14px;
            }

            .sigef-cpv-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .sigef-cpv-stage {
                min-height: 360px;
                padding: 12px;
            }

            .sigef-cpv-card-viewport {
                min-height: 320px;
                height: 62vh;
            }
        }
    </style>

    <div class="sigef-cpv-header">
        <div class="sigef-cpv-title-wrap">
            <div class="sigef-cpv-title">
                <x-filament::icon icon="heroicon-o-identification" class="sigef-cpv-icon" />
                <span>{{ $documentName }}</span>
            </div>

            <div class="sigef-cpv-badges">
                <span class="sigef-cpv-badge primary">{{ $orientationLabel }}</span>
                <span class="sigef-cpv-badge muted" x-text="isFlipped ? 'Verso' : 'Frente'">Frente</span>
                <span class="sigef-cpv-badge info">{{ $statusLabel ?? 'PRE-VISUALIZACAO' }}</span>
            </div>
        </div>

        <div class="sigef-cpv-actions">
            <button type="button" class="sigef-cpv-btn outline" x-on:click.prevent.stop="printCard()" x-bind:disabled="isPrinting">
                <span x-show="!isPrinting">
                    <x-filament::icon icon="heroicon-o-printer" class="sigef-cpv-btn-icon" />
                </span>
                <span x-show="isPrinting" class="sigef-cpv-spin" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="sigef-cpv-btn-icon" />
                </span>
                <span x-text="isPrinting ? 'A preparar...' : 'Imprimir'">Imprimir</span>
            </button>

            <button type="button" class="sigef-cpv-btn accent" x-on:click.prevent.stop="toggleExpand()">
                <span x-show="!isExpanded">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="sigef-cpv-btn-icon" />
                </span>
                <span x-show="isExpanded" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="sigef-cpv-btn-icon" />
                </span>
                <span x-text="isExpanded ? 'Reduzir' : 'Expandir'">Expandir</span>
            </button>
        </div>
    </div>

    <div class="sigef-cpv-stage">
        <div class="sigef-cpv-card-viewport" x-ref="cardViewport">
            <div class="sigef-cpv-scale-shell" x-bind:style="scaleShellStyle()">
                <div class="sigef-cpv-flipper" x-bind:class="{ 'is-flipped': isFlipped }" x-bind:style="flipperStyle()">
                    <div class="sigef-cpv-face front">
                        <iframe id="{{ $frameId }}-front" src="{{ $embeddedFrontUrl }}" scrolling="no" title="Frente do cartao"></iframe>
                    </div>
                    <div class="sigef-cpv-face back">
                        <iframe id="{{ $frameId }}-back" src="{{ $embeddedBackUrl }}" scrolling="no" title="Verso do cartao"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="sigef-cpv-controls">
            <span class="sigef-cpv-face-label" x-bind:class="{ 'active': !isFlipped }">Frente</span>
            <button type="button" class="sigef-cpv-toggle" x-on:click.prevent.stop="toggleFlip()">
                <x-filament::icon icon="heroicon-o-arrow-path" class="sigef-cpv-toggle-icon" />
                <span>Virar Cartao</span>
            </button>
            <span class="sigef-cpv-face-label" x-bind:class="{ 'active': isFlipped }">Verso</span>
        </div>

        <div class="sigef-cpv-hint">Pre-visualize frente e verso do cartao antes de imprimir.</div>
    </div>
</div>
