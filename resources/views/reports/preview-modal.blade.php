@php
    $printUrl = preg_replace('/([?&])autoprint=0\b/', '$1autoprint=1', $previewUrl);

    if ($printUrl === $previewUrl && ! str_contains($printUrl, 'autoprint=')) {
        $printUrl .= (str_contains($printUrl, '?') ? '&' : '?').'autoprint=1';
    }
@endphp

<div
    class="report-preview-modal-wrapper"
    id="sigef-report-preview-viewer"
    x-data="{
        isExpanded: false,
        isPrinting: false,
        isLoading: true,
        toggleExpand() {
            this.isExpanded = ! this.isExpanded;

            let el = this.$el;

            while (el && el !== document.body) {
                el = el.parentElement;

                if (el && el.className && el.className.toString().indexOf('fi-modal-window') >= 0) {
                    el.classList.toggle('rpv-expanded', this.isExpanded);
                    break;
                }
            }
        },
        printReport() {
            if (this.isPrinting) {
                return;
            }

            this.isPrinting = true;

            const frame = document.getElementById('sigef-report-preview-frame');

            try {
                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    setTimeout(() => frame.contentWindow.print(), 80);
                    setTimeout(() => { this.isPrinting = false; }, 900);
                    return;
                }
            } catch (error) {}

            window.open(@js($printUrl), '_blank');
            setTimeout(() => { this.isPrinting = false; }, 900);
        },
        frameLoaded() {
            this.isLoading = false;
        }
    }"
>
    <style>
        .report-preview-modal-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .rpv-expanded {
            max-width: 96vw !important;
            width: 96vw !important;
            margin-left: auto !important;
            margin-right: auto !important;
            transition: max-width .3s ease, width .3s ease;
        }

        .rpv-expanded .rpv-frame-shell,
        .rpv-expanded .rpv-frame-shell iframe {
            height: 78vh;
        }

        .rpv-header {
            align-items: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            color: #f1f5f9;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 16px;
            padding: 12px 20px;
        }

        .rpv-title-wrap {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
        }

        .rpv-title {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            font-size: .78rem;
            font-weight: 700;
            gap: 8px;
            min-width: 0;
        }

        .rpv-title-icon,
        .rpv-btn-icon {
            flex-shrink: 0;
            height: 15px;
            width: 15px;
        }

        .rpv-title-icon {
            opacity: .72;
        }

        .rpv-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .rpv-badge {
            border-radius: 9999px;
            font-size: .55rem;
            font-weight: 800;
            letter-spacing: .06em;
            padding: 3px 10px;
            text-transform: uppercase;
        }

        .rpv-badge-primary {
            background: rgba(59, 130, 246, .85);
            color: #fff;
        }

        .rpv-actions {
            align-items: center;
            display: flex;
            flex-shrink: 0;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .rpv-btn {
            align-items: center;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            letter-spacing: .04em;
            line-height: 1;
            padding: 7px 14px;
            text-decoration: none;
            text-transform: uppercase;
            transition: all .2s ease;
            white-space: nowrap;
        }

        .rpv-btn-outline {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #cbd5e1;
        }

        .rpv-btn-outline:hover {
            background: rgba(255, 255, 255, .15);
            color: #f1f5f9;
        }

        .rpv-btn-accent {
            background: #3b82f6;
            color: #fff;
        }

        .rpv-btn-accent:hover {
            background: #2563eb;
            box-shadow: 0 2px 8px rgba(59, 130, 246, .35);
        }

        .rpv-btn:disabled {
            cursor: wait;
            opacity: .6;
        }

        @keyframes rpv-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .rpv-spin {
            animation: rpv-spin .8s linear infinite;
        }

        .rpv-stage {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            min-height: 520px;
            padding: 18px;
            position: relative;
        }

        .rpv-frame-shell {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, .08);
            flex: 1;
            min-height: 74vh;
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .rpv-frame-shell iframe {
            background: #fff;
            border: 0;
            display: block;
            height: 74vh;
            width: 100%;
        }

        .rpv-loading {
            align-items: center;
            background: rgba(248, 250, 252, .86);
            color: #0f172a;
            display: none;
            font-size: 11px;
            font-weight: 800;
            inset: 0;
            justify-content: center;
            letter-spacing: .05em;
            position: absolute;
            text-transform: uppercase;
            z-index: 2;
        }

        .rpv-loading.active {
            display: flex;
        }

        .rpv-hint {
            color: #64748b;
            font-size: 11px;
            letter-spacing: .02em;
            padding: 10px 4px 0;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .rpv-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 12px 14px;
            }

            .rpv-actions {
                justify-content: flex-start;
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .rpv-stage {
                min-height: 320px;
                padding: 12px;
            }

            .rpv-frame-shell,
            .rpv-frame-shell iframe {
                height: 64vh;
                min-height: 64vh;
            }
        }
    </style>

    <div class="rpv-header">
        <div class="rpv-title-wrap">
            <div class="rpv-title">
                <x-filament::icon icon="heroicon-o-document-chart-bar" class="rpv-title-icon" />
                <span>{{ $title }}</span>
            </div>

            <div class="rpv-badge-row">
                <span class="rpv-badge rpv-badge-primary">Relat&oacute;rio</span>
            </div>
        </div>

        <div class="rpv-actions">
            <button type="button" class="rpv-btn rpv-btn-outline" x-on:click.prevent.stop="printReport()" x-bind:disabled="isPrinting">
                <span x-show="!isPrinting">
                    <x-filament::icon icon="heroicon-o-printer" class="rpv-btn-icon" />
                </span>
                <span x-show="isPrinting" class="rpv-spin" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="rpv-btn-icon" />
                </span>
                <span x-text="isPrinting ? 'A preparar...' : 'Imprimir'">Imprimir</span>
            </button>

            <button type="button" class="rpv-btn rpv-btn-accent" x-on:click.prevent.stop="toggleExpand()">
                <span x-show="!isExpanded">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="rpv-btn-icon" />
                </span>
                <span x-show="isExpanded" style="display: none;">
                    <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="rpv-btn-icon" />
                </span>
                <span x-text="isExpanded ? 'Reduzir' : 'Expandir'">Expandir</span>
            </button>
        </div>
    </div>

    <div class="rpv-stage">
        <div class="rpv-frame-shell">
            <div class="rpv-loading" x-bind:class="{ 'active': isLoading }">A preparar relat&oacute;rio...</div>
            <iframe
                id="sigef-report-preview-frame"
                src="{{ $previewUrl }}"
                x-on:load="frameLoaded()"
                title="{{ $title }}"
                scrolling="auto"
            ></iframe>
        </div>
        <div class="rpv-hint">Pr&eacute;-visualize o relat&oacute;rio com margens de impress&atilde;o antes de imprimir.</div>
    </div>
</div>
