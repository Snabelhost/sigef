@php
    use App\Models\CardTemplate;

    $type = $template->card_type ?? CardTemplate::TYPE_STUDENT;
    $templateStyle = $template->style ?: CardTemplate::STYLE_STUDENT;
    $forceHorizontalAccess = in_array($type, [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF], true);
    $usesAccessVertical = $templateStyle === CardTemplate::STYLE_PROFESSOR_VERTICAL && ! $forceHorizontalAccess;
    $orientation = $template->orientation ?: CardTemplate::ORIENTATION_HORIZONTAL;
    $orientation = $forceHorizontalAccess ? CardTemplate::ORIENTATION_HORIZONTAL : ($usesAccessVertical ? CardTemplate::ORIENTATION_VERTICAL : $orientation);
    $isHorizontal = $orientation !== CardTemplate::ORIENTATION_VERTICAL;
    $printScaleMode = $printScaleMode ?? request()->query('print_scale');
    $printOutputScale = $printScaleMode === 'large' ? 1.65 : 1.0;
    $cardWidthMmValue = $isHorizontal ? 85.60 : 53.98;
    $cardHeightMmValue = $isHorizontal ? 53.98 : 85.60;
    $printWidthMmValue = $printScaleMode === 'large'
        ? ($cardWidthMmValue * $printOutputScale) + 4.4
        : ($isHorizontal ? 90.0 : 58.0);
    $printHeightMmValue = $printScaleMode === 'large'
        ? ($cardHeightMmValue * $printOutputScale) + 4.4
        : ($isHorizontal ? 58.0 : 90.0);
    $formatSheetMm = fn (float $value): string => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'mm';
    $printWidthMm = $formatSheetMm($printWidthMmValue);
    $printHeightMm = $formatSheetMm($printHeightMmValue);
    $baseCardWidthPx = $isHorizontal ? 506 : 306;
    $baseCardHeightPx = $isHorizontal ? 320 : 485;
    $printWidthPx = $isHorizontal ? 323.53 : 204.02;
    $printHeightPx = $isHorizontal ? 204.02 : 323.53;
    $printScale = min($printWidthPx / $baseCardWidthPx, $printHeightPx / $baseCardHeightPx) * $printOutputScale;
    $faces = match ($showFace ?? null) {
        'front' => ['front'],
        'back' => ['back'],
        default => ['front', 'back'],
    };
    $isEmbedded = ! empty($embeddedMode);
    $screenSheetWidth = $isEmbedded ? "{$baseCardWidthPx}px" : $printWidthMm;
    $screenSheetHeight = $isEmbedded ? "{$baseCardHeightPx}px" : $printHeightMm;
    $screenBodyBackground = $isEmbedded ? 'transparent' : '#eef3f8';
    $screenSheetBackground = $isEmbedded ? 'transparent' : '#ffffff';
    $screenCardZoom = $isEmbedded ? '1' : number_format($printScale, 6, '.', '');
    $printCardZoom = number_format($printScale, 6, '.', '');
    $title = $documentName ?? (($template->name ?: 'Cartao').' - '.($payload['name'] ?? 'SIGEF'));
    $printTemplateKey = $template->getKey() ?: substr(md5($title), 0, 10);
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ $printWidthMm }} {{ $printHeightMm }};
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: {{ $screenSheetWidth }};
            min-width: {{ $screenSheetWidth }};
            min-height: {{ $screenSheetHeight }};
            margin: 0;
            padding: 0;
            background: {{ $screenBodyBackground }};
            color: #0f172a;
            overflow: visible;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body,
        body * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .sigef-card-print-page {
            min-height: {{ ! empty($embeddedMode) ? 'auto' : '100vh' }};
            display: {{ ! empty($embeddedMode) ? 'block' : 'flex' }};
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin: 0;
            padding: {{ ! empty($embeddedMode) ? '0' : '24px' }};
            background: transparent;
        }

        .sigef-card-print-hint {
            display: {{ $isEmbedded ? 'none' : 'block' }};
            font-size: 12px;
            color: #475569;
            text-align: center;
        }

        .sigef-card-print-sheets {
            display: {{ ! empty($embeddedMode) ? 'block' : 'flex' }};
            flex-wrap: wrap;
            justify-content: center;
            gap: {{ ! empty($embeddedMode) ? '0' : '20px' }};
        }

        .sigef-card-print-sheet {
            position: relative;
            width: {{ $screenSheetWidth }};
            height: {{ $screenSheetHeight }};
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: {{ $screenSheetBackground }};
            break-after: page;
            page-break-after: always;
            page-break-inside: avoid;
        }

        .sigef-card-print-sheet:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .sigef-card-print-card {
            position: relative;
            width: {{ $baseCardWidthPx }}px;
            height: {{ $baseCardHeightPx }}px;
            margin: 0;
            padding: 0;
            overflow: hidden;
            zoom: {{ $screenCardZoom }};
        }

        .sigef-card-print-card > .sigef-card-preview-modal {
            width: {{ $baseCardWidthPx }}px !important;
            height: {{ $baseCardHeightPx }}px !important;
            min-width: {{ $baseCardWidthPx }}px !important;
            min-height: {{ $baseCardHeightPx }}px !important;
            max-width: {{ $baseCardWidthPx }}px !important;
            max-height: {{ $baseCardHeightPx }}px !important;
        }

        @media print {
            @page {
                size: {{ $printWidthMm }} {{ $printHeightMm }};
                margin: 0;
            }

            html,
            body {
                background: #ffffff;
                width: {{ $printWidthMm }} !important;
                min-width: {{ $printWidthMm }} !important;
                height: auto;
                min-height: {{ $printHeightMm }} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            .sigef-card-print-page {
                min-height: auto;
                padding: 0;
                gap: 0;
                display: block;
            }

            .sigef-card-print-hint {
                display: none !important;
            }

            .sigef-card-print-sheets {
                display: block;
                gap: 0;
            }

            .sigef-card-print-sheet {
                width: {{ $printWidthMm }} !important;
                height: {{ $printHeightMm }} !important;
                background: #ffffff !important;
            }

            .sigef-card-print-card {
                zoom: {{ $printCardZoom }} !important;
            }
        }
    </style>
</head>
<body class="{{ ! empty($embeddedMode) ? 'embedded-print-preview' : '' }}">
    <main class="sigef-card-print-page">
        <div class="sigef-card-print-hint">
            {{ ($previewMode ?? false) ? 'Pre-visualizacao do modelo de cartao.' : 'A janela de impressao sera aberta automaticamente.' }}
        </div>

        <div class="sigef-card-print-sheets">
            @foreach ($faces as $face)
                <section class="sigef-card-print-sheet">
                    <div class="sigef-card-print-card">
                        @include('cards.preview-modal', [
                            'template' => $template,
                            'payload' => $payload,
                            'viewerId' => 'sigef-card-print-'.$printTemplateKey.'-'.$face,
                            'entityLabel' => $entityLabel ?? 'Modelos',
                            'documentName' => $documentName ?? $title,
                            'statusLabel' => $statusLabel ?? 'PRE-VISUALIZACAO',
                            'statusColor' => $statusColor ?? 'info',
                            'printPageMode' => true,
                            'suppressPrintCss' => true,
                            'showFace' => $face,
                        ])
                    </div>
                </section>
            @endforeach
        </div>
    </main>

    @if (($autoPrint ?? (! ($previewMode ?? false))))
        <script>
            const shouldAutoClose = new URLSearchParams(window.location.search).get('autoclose') === '1';

            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.focus();
                    window.print();
                }, 350);
            });

            if (shouldAutoClose) {
                window.addEventListener('afterprint', function () {
                    setTimeout(function () {
                        window.close();
                    }, 200);
                });
            }
        </script>
    @endif
</body>
</html>
