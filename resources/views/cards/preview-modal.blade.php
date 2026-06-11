@php
    use App\Models\CardTemplate;
    use Illuminate\Support\Str;

    $printPageMode = (bool) ($printPageMode ?? false);
    $suppressPrintCss = (bool) ($suppressPrintCss ?? false);
    $showFace = $showFace ?? null;
    $type = $template->card_type ?? CardTemplate::TYPE_STUDENT;
    $templateStyle = $template->style ?: CardTemplate::STYLE_STUDENT;
    $forceHorizontalAccess = in_array($type, [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF], true);
    $usesAccessVertical = $templateStyle === CardTemplate::STYLE_PROFESSOR_VERTICAL && ! $forceHorizontalAccess;
    $orientation = $template->orientation ?: CardTemplate::ORIENTATION_HORIZONTAL;
    $orientation = $forceHorizontalAccess ? CardTemplate::ORIENTATION_HORIZONTAL : ($usesAccessVertical ? CardTemplate::ORIENTATION_VERTICAL : $orientation);
    $isVertical = $orientation === CardTemplate::ORIENTATION_VERTICAL;
    $usesHorizontalAccess = $forceHorizontalAccess && ! $isVertical;
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
    $headerText = $template->header_text_color ?: $frontText;
    $backText = $template->back_text_color ?: '#111827';
    $frontBackgroundColor = $template->front_background_color ?: $template->primary_color ?: '#ffffff';
    $backBackgroundColor = $template->back_background_color ?: '#ffffff';
    $frontStyle = $template->front_background_url
        ? "background-color: {$frontBackgroundColor}; background-image: url('{$template->front_background_url}');"
        : "background-color: {$frontBackgroundColor};";
    $backStyle = $template->back_background_url
        ? "background-color: {$backBackgroundColor}; background-image: url('{$template->back_background_url}');"
        : "background-color: {$backBackgroundColor};";
    $screenWidthPx = $isVertical ? 306 : 506;
    $screenHeightPx = $isVertical ? 485 : 320;
    $printWidthMm = $isVertical ? 54 : 85.6;
    $printHeightMm = $isVertical ? 85.6 : 54;
    $screenWidth = "{$screenWidthPx}px";
    $screenHeight = "{$screenHeightPx}px";
    $printWidth = "{$printWidthMm}mm";
    $printHeight = "{$printHeightMm}mm";
    $printScale = round((($printWidthMm / 25.4) * 96) / $screenWidthPx, 6);
    $formatMm = fn (float $value): string => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'mm';
    $printSheetWidth = $formatMm($printWidthMm + ($isVertical ? 4 : 4.4));
    $printSheetHeight = $formatMm($printHeightMm + ($isVertical ? 4.4 : 4));
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
    $defaultAvatarUrl = 'https://ui-avatars.com/api/?name='.rawurlencode($name ?: 'SIGEF').'&background=000000&color=ffffff&size=256&font-size=0.4&bold=true';
    $providedPhotoUrl = $payload['photo_url'] ?? null;
    $photoUrl = filled($providedPhotoUrl) ? $providedPhotoUrl : $defaultAvatarUrl;
    $brandName = $payload['institution_name'] ?? $template->brand_name ?? 'SIGEF';
    $institutionLocation = $payload['institution_location'] ?? $template->address_line ?? null;
    $frontTitle = $payload['front_title'] ?? $template->front_title ?? 'CARTAO DE IDENTIFICACAO';
    $cardNumber = $payload['card_number'] ?? $payload['number'] ?? $documentNumber;
    $showQrCode = (bool) ($payload['show_qr_code'] ?? $template->show_qr_code ?? true);
    $accessUntil = $payload['access_until'] ?? $payload['valid_until'] ?? ('Dezembro de '.now()->addYears(2)->format('Y'));
    $rawFooterText = $payload['footer_text'] ?? $template->footer_text ?? 'Este cartao identifica o portador no SIGEF.';
    $accessFooterText = str_replace(['{access_until}', '{valid_until}', '{validade}'], $accessUntil, $rawFooterText);
    $accessRoleTitle = mb_strtoupper((string) $entityTitle);
    $accessInstitutionSubtitle = $payload['subtitle'] ?? $template->subtitle ?? null;
    if (
        filled($accessInstitutionSubtitle)
        && Str::lower(Str::ascii(trim((string) $accessInstitutionSubtitle))) === Str::lower(Str::ascii(trim((string) $brandName)))
    ) {
        $accessInstitutionSubtitle = null;
    }
    $accessSignatureLabel = $payload['signature_label'] ?? $template->signature_label ?? 'O DIRECTOR';
    $accessSignatoryName = $payload['signatory_name'] ?? $template->signatory_name ?? '';
    $accessSignatoryTitle = $payload['signatory_title'] ?? $template->signatory_title ?? '';
    $accessFrontHasImage = filled($template->front_background_url ?? null);
    $accessName = mb_strtoupper((string) $name);
    $accessPlacementItems = collect([
        ['CIA', filled($payload['cia'] ?? null) ? $payload['cia'] : '-'],
        ['PELOTÃO', filled($payload['platoon'] ?? null) ? $payload['platoon'] : '-'],
        ['SECÇÃO', filled($payload['section'] ?? null) ? $payload['section'] : '-'],
    ]);
    $studentPlacementSummary = $accessPlacementItems
        ->map(fn (array $row): string => "{$row[0]}: {$row[1]}")
        ->implode(' / ');
    $accessFrontRows = collect(match ($type) {
        CardTemplate::TYPE_PROFESSOR => [
            [$documentLabel, $documentNumber],
            ['POSTO', $payload['rank'] ?? $payload['position'] ?? null],
            ['DISCIPLINA', $payload['discipline'] ?? null],
            ['DEPARTAMENTO', $payload['department'] ?? null],
            ['GRAU ACADEMICO', $payload['academic_degree'] ?? $payload['rank'] ?? $academicYearValue ?? null],
        ],
        CardTemplate::TYPE_STAFF => [
            [$documentLabel, $documentNumber],
            ['DEPARTAMENTO', $payload['department'] ?? null],
            ['FUNCAO', $payload['function'] ?? null],
            ['POSTO', $payload['rank'] ?? $payload['position'] ?? null],
        ],
        default => [
            [$documentLabel, $documentNumber],
            ['CURSO', $payload['course'] ?? null],
            ['TURMA', $payload['class'] ?? null],
            ['ANO ACADEMICO', $academicYearValue],
        ],
    })->filter(fn (array $row): bool => filled($row[1] ?? null) && ($row[1] ?? '-') !== '-')->values();

    $frontRows = collect([
        [$documentLabel, $documentNumber],
        ['Curso', $payload['course'] ?? null],
        ['Turma', $payload['class'] ?? null],
        ['CIA / Pelotão / Secção', $type === CardTemplate::TYPE_STUDENT ? $studentPlacementSummary : null],
        ['Ano académico', $academicYearValue],
        ['Função', $payload['function'] ?? null],
        ['Departamento', $payload['department'] ?? null],
        ['Disciplina', $payload['discipline'] ?? null],
        ['Posto', $payload['rank'] ?? $payload['position'] ?? null],
        ['Regime', $payload['regime'] ?? null],
        ['Grupo sanguíneo', $payload['blood_type'] ?? null],
        ['Colocação', $type === CardTemplate::TYPE_STUDENT ? null : ($payload['placement'] ?? null)],
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

    $frontBackgroundPath = trim((string) ($template->front_background_path ?? ''));
    $backBackgroundPath = trim((string) ($template->back_background_path ?? ''));
    $knownHorizontalFrontBackgrounds = [
        'images/card-templates/student-cadet/front-template.png',
        'images/card-templates/staff-effective/front-template.png',
        'images/cards/fundo_card.png',
    ];
    $knownHorizontalBackBackgrounds = [
        'images/card-templates/student-cadet/back-template.png',
        'images/cards/fundo_card.png',
    ];
    $horizontalHasCustomFrontBackground = $usesHorizontalAccess
        && filled($template->front_background_url ?? null)
        && ! in_array($frontBackgroundPath, $knownHorizontalFrontBackgrounds, true);
    $horizontalHasCustomBackBackground = $usesHorizontalAccess
        && filled($template->back_background_url ?? null)
        && ! in_array($backBackgroundPath, $knownHorizontalBackBackgrounds, true);
    $horizontalFrontStyle = $horizontalHasCustomFrontBackground
        ? "background-color: {$frontBackgroundColor}; background-image: url('{$template->front_background_url}');"
        : "background-color: {$frontBackgroundColor};";
    $horizontalBackStyle = $horizontalHasCustomBackBackground
        ? "background-color: {$backBackgroundColor}; background-image: url('{$template->back_background_url}');"
        : "background-color: {$backBackgroundColor};";
    $horizontalEntityLetter = mb_substr($accessRoleTitle ?: $entityTitle, 0, 1) ?: 'S';
    $horizontalBackTitle = trim((string) ($template->back_title ?? ''));
    $horizontalBackTitleAscii = Str::lower(Str::ascii($horizontalBackTitle));
    $horizontalBackTitle = $horizontalBackTitle === '' || str_starts_with($horizontalBackTitleAscii, 'identificacao')
        ? 'PRERROGATIVA'
        : $horizontalBackTitle;
    $horizontalBackText = trim(str_replace(
        ['{tipo}', '{entidade}', '{instituicao}', '{institution}'],
        [$accessRoleTitle, $accessRoleTitle, $brandName, $brandName],
        (string) $accessFooterText,
    ));
    $horizontalBackTextAscii = Str::lower(Str::ascii($horizontalBackText));
    if ($horizontalBackText === '' || str_contains($horizontalBackTextAscii, 'este cartao identifica')) {
        $horizontalBackText = 'O presente passe e intransmissivel e tem a finalidade de identificar o portador, na qualidade de '.$accessRoleTitle.' do '.$brandName.'.';
    }
    $horizontalFrontRows = collect(match ($type) {
        CardTemplate::TYPE_PROFESSOR => [
            ['NOME', $name, true],
            [$payload['document_label'] ?? $documentLabel, $payload['document_number'] ?? $documentNumber, false],
            ['POSTO', $payload['rank'] ?? $payload['position'] ?? null, false],
            ['DISCIPLINA', $payload['discipline'] ?? null, false],
            ['DEPARTAMENTO', $payload['department'] ?? null, false],
            ['GRAU ACADEMICO', $payload['academic_degree'] ?? $payload['rank'] ?? null, false],
        ],
        CardTemplate::TYPE_STAFF => [
            ['NOME', $name, true],
            ['POSTO', $payload['rank'] ?? $payload['position'] ?? null, false],
            ['FUNCAO', $payload['function'] ?? null, false],
            [$payload['document_label'] ?? $documentLabel, $payload['document_number'] ?? $documentNumber, false],
            ['GRUPO SANGUINEO', $payload['blood_type'] ?? null, false],
        ],
        default => [],
    })->filter(fn (array $row): bool => filled($row[1] ?? null) && ($row[1] ?? '-') !== '-')->values();
@endphp

<div
    id="{{ $viewerId }}"
    class="sigef-card-preview-modal {{ $isVertical ? 'is-vertical' : 'is-horizontal' }} {{ $usesAccessVertical ? 'uses-access-vertical' : '' }} {{ $usesHorizontalAccess ? 'uses-horizontal-access' : '' }} is-card-type-{{ $type }} {{ $printPageMode ? 'is-print-page' : '' }}"
    data-print-title="{{ e($documentName) }}"
    data-print-sheet-width="{{ $printSheetWidth }}"
    data-print-sheet-height="{{ $printSheetHeight }}"
    data-print-scale="{{ $printScale }}"
    data-screen-width="{{ $screenWidth }}"
    data-screen-height="{{ $screenHeight }}"
    data-screen-width-px="{{ $screenWidthPx }}"
    data-screen-height-px="{{ $screenHeightPx }}"
    x-data="{
        side: 'front',
        isExpanded: false,
        isPrinting: false,
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
            if (this.isPrinting) {
                return;
            }

            this.isPrinting = true;

            const faces = Array.from(this.$el.querySelectorAll('.sigef-card-preview-face'));

            if (! faces.length) {
                window.print();
                this.isPrinting = false;
                return;
            }

            const cleanAlpineAttributes = (node) => {
                if (node.nodeType !== 1) {
                    return;
                }

                Array.from(node.attributes).forEach((attribute) => {
                    if (attribute.name.indexOf('x-') === 0 || attribute.name.indexOf(':') === 0 || attribute.name.indexOf('@') === 0) {
                        node.removeAttribute(attribute.name);
                    }
                });

                Array.from(node.children).forEach((child) => cleanAlpineAttributes(child));
            };
            const stripPrintCss = (css) => {
                let result = '';
                let index = 0;

                while (index < css.length) {
                    const mediaIndex = css.indexOf('@media print', index);

                    if (mediaIndex < 0) {
                        result += css.slice(index);
                        break;
                    }

                    result += css.slice(index, mediaIndex);

                    const openIndex = css.indexOf('{', mediaIndex);

                    if (openIndex < 0) {
                        break;
                    }

                    let depth = 1;
                    let cursor = openIndex + 1;

                    while (cursor < css.length && depth > 0) {
                        if (css[cursor] === '{') {
                            depth++;
                        } else if (css[cursor] === '}') {
                            depth--;
                        }

                        cursor++;
                    }

                    index = cursor;
                }

                return result;
            };
            const escapeHtml = (value) => {
                const holder = document.createElement('div');
                holder.textContent = value || '';

                return holder.innerHTML;
            };
            const printTitle = this.$el.dataset.printTitle || 'Impressao do cartao';
            const sheetWidth = this.$el.dataset.printSheetWidth || '58mm';
            const sheetHeight = this.$el.dataset.printSheetHeight || '90mm';
            const screenWidth = this.$el.dataset.screenWidth || '306px';
            const screenHeight = this.$el.dataset.screenHeight || '485px';
            const screenWidthPx = Number(this.$el.dataset.screenWidthPx || 306);
            const screenHeightPx = Number(this.$el.dataset.screenHeightPx || 485);
            const printScale = Number(this.$el.dataset.printScale || 1);
            const styleBlock = this.$el.querySelector('style');
            const modalClasses = this.$el.className;
            const screenCss = styleBlock ? stripPrintCss(styleBlock.innerHTML) : '';
            const sheets = faces.map((face) => {
                const clone = face.cloneNode(true);
                clone.classList.add('is-active');
                cleanAlpineAttributes(clone);

                return '<section class=\'sigef-card-print-sheet\'><div class=\'' + modalClasses + ' sigef-card-print-root\'><div class=\'sigef-card-print-canvas\'>' + clone.outerHTML + '</div></div></section>';
            }).join('');
            const printCss = `
                @page {
                    size: ${sheetWidth} ${sheetHeight};
                    margin: 0;
                }

                * {
                    box-sizing: border-box;
                }

                html,
                body {
                    width: ${sheetWidth};
                    min-width: ${sheetWidth};
                    min-height: ${sheetHeight};
                    margin: 0;
                    padding: 0;
                    background: #ffffff;
                    overflow: visible;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                body,
                body * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                .sigef-card-print-pages {
                    display: block;
                    width: ${sheetWidth};
                    margin: 0 auto;
                    padding: 0;
                    background: #ffffff;
                }

                .sigef-card-print-sheet {
                    position: relative;
                    width: ${sheetWidth};
                    height: ${sheetHeight};
                    margin: 0 auto;
                    padding: 0;
                    overflow: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #ffffff;
                    break-after: page;
                    page-break-after: always;
                    page-break-inside: avoid;
                }

                .sigef-card-print-sheet:last-child {
                    break-after: auto;
                    page-break-after: auto;
                }

                .sigef-card-print-root.sigef-card-preview-modal {
                    position: relative !important;
                    inset: auto !important;
                    display: block !important;
                    width: ${screenWidth} !important;
                    height: ${screenHeight} !important;
                    min-width: ${screenWidth} !important;
                    min-height: ${screenHeight} !important;
                    max-width: ${screenWidth} !important;
                    max-height: ${screenHeight} !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    gap: 0 !important;
                    background: transparent !important;
                    overflow: visible !important;
                    zoom: ${printScale};
                }

                .sigef-card-print-canvas {
                    position: relative;
                    width: ${screenWidth};
                    height: ${screenHeight};
                    margin: 0;
                    padding: 0;
                    overflow: visible;
                }

                .sigef-card-print-root .sigef-card-preview-face {
                    position: relative !important;
                    inset: auto !important;
                    display: block !important;
                    width: ${screenWidth} !important;
                    height: ${screenHeight} !important;
                    min-width: ${screenWidth} !important;
                    min-height: ${screenHeight} !important;
                    max-width: ${screenWidth} !important;
                    max-height: ${screenHeight} !important;
                    margin: 0 !important;
                    opacity: 1 !important;
                    transform: none !important;
                    transition: none !important;
                    box-shadow: none !important;
                    page-break-inside: avoid;
                    break-inside: avoid;
                }

                .sigef-card-print-root .sigef-card-preview-back {
                    transform: none !important;
                }

                @media print {
                    @page {
                        size: ${sheetWidth} ${sheetHeight};
                        margin: 0;
                    }

                    html,
                    body,
                    .sigef-card-print-pages {
                        width: ${sheetWidth} !important;
                        min-width: ${sheetWidth} !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        overflow: visible !important;
                        background: #ffffff !important;
                    }
                }
            `;
            const frame = document.createElement('iframe');

            frame.setAttribute('title', printTitle);
            frame.style.cssText = 'position:fixed;top:-10000px;left:-10000px;width:' + Math.max(1100, Math.ceil(screenWidthPx * Math.max(printScale, 1)) + 80) + 'px;height:' + Math.max(1000, Math.ceil(screenHeightPx * Math.max(printScale, 1)) + 80) + 'px;opacity:0;border:0;pointer-events:none;';
            document.body.appendChild(frame);

            const printWindow = frame.contentWindow;
            const printDocument = printWindow.document;
            let cleaned = false;
            const cleanup = () => {
                if (cleaned) {
                    return;
                }

                cleaned = true;

                setTimeout(() => {
                    frame.remove();
                    this.isPrinting = false;
                }, 350);
            };
            const waitForAssets = () => {
                const imagePromises = Array.from(printDocument.images)
                    .filter((image) => ! image.complete)
                    .map((image) => new Promise((resolve) => {
                        image.addEventListener('load', resolve, { once: true });
                        image.addEventListener('error', resolve, { once: true });
                    }));
                const fontPromise = printDocument.fonts ? printDocument.fonts.ready.catch(() => {}) : Promise.resolve();

                return Promise.race([
                    Promise.all([...imagePromises, fontPromise]),
                    new Promise((resolve) => setTimeout(resolve, 1800)),
                ]);
            };

            printDocument.open();
            printDocument.write('<!doctype html><html lang=\'pt\'><head><meta charset=\'utf-8\'><meta name=\'viewport\' content=\'width=device-width, initial-scale=1\'><title>' + escapeHtml(printTitle) + '</title><style>' + screenCss + '</style><style>' + printCss + '</style></head><body><main class=\'sigef-card-print-pages\'>' + sheets + '</main></body></html>');
            printDocument.close();
            printWindow.addEventListener('afterprint', cleanup, { once: true });

            setTimeout(() => {
                waitForAssets().then(() => {
                    try {
                        printWindow.focus();
                        printWindow.print();
                        setTimeout(cleanup, 10000);
                    } catch (error) {
                        cleanup();
                        window.print();
                    }
                });
            }, 120);
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
            --sigef-card-print-scale: {{ $printScale }};
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
            color: {{ $headerText }};
            font-size: {{ $isVertical ? '10px' : '11px' }};
            font-weight: 950;
            line-height: 1.18;
            text-transform: uppercase;
        }

        .sigef-card-preview-location {
            margin-top: 3px;
            color: {{ $headerText }};
            font-size: 8px;
            font-weight: 700;
            opacity: .72;
        }

        .sigef-card-preview-front-number {
            margin-top: 5px;
            color: {{ $headerText }};
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

        .sigef-card-preview-modal.uses-horizontal-access {
            --sigef-card-screen-width: 506px;
            --sigef-card-screen-height: 320px;
            --sigef-card-print-width: 85.6mm;
            --sigef-card-print-height: 54mm;
        }

        .sigef-horizontal-card {
            padding: 0;
            border: 0;
            border-radius: 10px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 22px 45px rgba(15, 23, 42, .16);
        }

        .sigef-horizontal-layer {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            color: #050816;
            font-family: "Montserrat SIGEF", Inter, ui-sans-serif, system-ui, sans-serif;
        }

        .sigef-horizontal-corner {
            position: absolute;
            left: -48px;
            bottom: -60px;
            width: 166px;
            height: 140px;
            border-radius: 50%;
            background: #0715d8;
            pointer-events: none;
        }

        .sigef-horizontal-watermark {
            position: absolute;
            left: 50%;
            top: 72px;
            width: 238px;
            height: 220px;
            object-fit: contain;
            opacity: .14;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .sigef-horizontal-logo {
            position: absolute;
            top: 132px;
            left: 36px;
            z-index: 2;
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .sigef-horizontal-title {
            position: absolute;
            top: 18px;
            left: 24px;
            right: 24px;
            z-index: 1;
            color: {{ $headerText }};
            font-size: 15px;
            font-weight: 950;
            line-height: 1.22;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-horizontal-subtitle {
            display: block;
            margin-top: 2px;
        }

        .sigef-horizontal-mark {
            position: absolute;
            left: 18px;
            top: 142px;
            z-index: 1;
            width: 96px;
            height: 92px;
            color: {{ $template->secondary_color ?: '#ff1208' }};
            font-family: Georgia, "Times New Roman", serif;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-horizontal-mark-letter {
            position: absolute;
            inset: -28px 0 auto;
            font-size: 96px;
            font-weight: 900;
            line-height: 1;
        }

        .sigef-horizontal-mark-word {
            position: absolute;
            left: 0;
            right: 0;
            top: 46px;
            font-size: 16px;
            font-weight: 900;
            line-height: 1;
            overflow-wrap: anywhere;
        }

        .sigef-card-preview-modal.uses-horizontal-access .sigef-horizontal-mark {
            display: none;
        }

        .sigef-horizontal-info {
            position: absolute;
            left: 128px;
            top: 108px;
            z-index: 1;
            width: 246px;
            display: grid;
            gap: 2px;
            color: #050816;
            font-size: 12.4px;
            font-weight: 500;
            line-height: 1.12;
            text-transform: uppercase;
        }

        .sigef-horizontal-row {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .sigef-horizontal-row strong {
            font-weight: 950;
        }

        .sigef-horizontal-row span {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-weight: 500;
        }

        .sigef-horizontal-row span.is-highlight {
            color: {{ $template->secondary_color ?: '#ff1208' }};
            font-size: 13.3px;
            font-weight: 500;
        }

        .sigef-horizontal-name {
            color: {{ $template->secondary_color ?: '#ff1208' }};
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.04;
            overflow-wrap: anywhere;
        }

        .sigef-horizontal-photo {
            position: absolute;
            top: 139px;
            right: 15px;
            z-index: 1;
            width: 128px;
            height: 142px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 2px solid #1f2937;
            border-radius: 16px;
            background: #e8f1ff;
            color: #07185f;
            font-size: 28px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .sigef-horizontal-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sigef-horizontal-photo.is-empty {
            background: var(--sigef-card-primary);
            border-color: var(--sigef-card-primary);
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-title,
        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-title {
            top: 28px;
            left: 24px;
            right: 24px;
            font-size: 16px;
            line-height: 1.12;
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-logo,
        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-logo {
            top: 126px;
            left: 16px;
            width: 100px;
            height: 100px;
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-watermark,
        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-watermark {
            top: 96px;
            width: 204px;
            height: 190px;
            opacity: .1;
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-info {
            left: 124px;
            top: 126px;
            width: 236px;
            gap: 2px;
            font-size: 16px;
            line-height: 1.06;
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-name {
            font-size: 20.4px;
            line-height: .96;
        }

        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-info {
            left: 124px;
            top: 126px;
            width: 236px;
            gap: 4px;
            font-size: 12.6px;
            line-height: 1.14;
        }

        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-name {
            font-size: 15.8px;
            line-height: 1.08;
        }

        .sigef-card-preview-modal.is-card-type-staff .sigef-horizontal-photo,
        .sigef-card-preview-modal.is-card-type-professor .sigef-horizontal-photo {
            top: 120px;
            right: 16px;
            width: 126px;
            height: 150px;
            border-radius: 14px;
        }

        .sigef-horizontal-back .sigef-horizontal-layer {
            display: block;
            padding: 0;
            text-align: center;
        }

        .sigef-horizontal-back-title {
            position: absolute;
            top: 30px;
            left: 22px;
            right: 22px;
            z-index: 1;
            color: {{ $backText }};
            font-size: 24px;
            font-weight: 950;
            line-height: 1;
            text-transform: uppercase;
        }

        .sigef-horizontal-back-text {
            position: absolute;
            top: 76px;
            left: 48px;
            right: 48px;
            z-index: 1;
            color: {{ $backText }};
            font-size: 13.2px;
            font-weight: 950;
            line-height: 1.38;
            text-transform: uppercase;
        }

        .sigef-horizontal-signature {
            position: absolute;
            left: 148px;
            bottom: 30px;
            z-index: 1;
            width: 190px;
            display: grid;
            justify-items: center;
            gap: 0;
            color: {{ $backText }};
            line-height: 1.05;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-horizontal-signature-label {
            font-size: 14px;
            font-weight: 950;
        }

        .sigef-horizontal-signature img {
            width: 128px;
            height: 40px;
            object-fit: contain;
            margin: -2px 0 -6px;
            mix-blend-mode: multiply;
        }

        .sigef-horizontal-signatory-name {
            font-size: 17px;
            font-weight: 950;
        }

        .sigef-horizontal-signatory-title {
            font-size: 10px;
            font-weight: 950;
        }

        .sigef-horizontal-qr {
            position: absolute;
            right: 37px;
            bottom: 24px;
            z-index: 1;
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(15, 23, 42, .14);
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .14);
        }

        .sigef-horizontal-qr img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .sigef-horizontal-qr-fallback {
            width: 50px;
            height: 50px;
            background:
                linear-gradient(90deg, #111827 6px, transparent 6px) 0 0 / 14px 14px,
                linear-gradient(#111827 6px, transparent 6px) 0 0 / 14px 14px,
                #fff;
        }

        .sigef-card-preview-modal.is-print-page {
            display: block;
            width: var(--sigef-card-screen-width);
            height: var(--sigef-card-screen-height);
            margin: 0;
            padding: 0;
            gap: 0;
            overflow: hidden;
            background: transparent;
        }

        .sigef-card-preview-modal.is-print-page .sigef-card-preview-stage {
            display: block;
            width: var(--sigef-card-screen-width);
            height: var(--sigef-card-screen-height);
            min-height: 0;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            overflow: hidden;
        }

        .sigef-card-preview-modal.is-print-page .sigef-card-preview-scene,
        .sigef-card-preview-modal.is-print-page .sigef-card-preview-stack {
            width: var(--sigef-card-screen-width);
            height: var(--sigef-card-screen-height);
            perspective: none;
            transform: none;
            transition: none;
        }

        .sigef-card-preview-modal.is-print-page .sigef-card-preview-face {
            position: absolute;
            inset: 0;
            width: var(--sigef-card-screen-width);
            height: var(--sigef-card-screen-height);
            opacity: 1;
            transform: none;
            box-shadow: none;
        }

        .sigef-card-preview-modal.uses-access-vertical {
            --sigef-card-screen-width: 306px;
            --sigef-card-screen-height: 485px;
            --sigef-card-print-width: 54mm;
            --sigef-card-print-height: 85.6mm;
        }

        .sigef-card-preview-modal.uses-access-vertical .sigef-card-preview-stage {
            background: #f1f6fd;
        }

        .sigef-access-card {
            padding: 0;
            border: 0;
            border-radius: 10px;
            background-color: #fff;
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 22px 45px rgba(15, 23, 42, .18);
        }

        .sigef-access-layer {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: transparent;
            color: #06145f;
            font-family: "Montserrat SIGEF", Inter, ui-sans-serif, system-ui, sans-serif;
        }

        .sigef-access-watermark {
            position: absolute;
            left: 50%;
            width: 258px;
            max-width: 86%;
            transform: translateX(-50%);
            object-fit: contain;
            opacity: .12;
            pointer-events: none;
            z-index: 0;
        }

        .sigef-access-front-watermark {
            top: 214px;
        }

        .sigef-access-front-header {
            position: relative;
            z-index: 1;
            display: grid;
            justify-items: center;
            gap: 4px;
            padding: 10px 14px 0;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-access-logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .sigef-access-institution {
            max-width: 282px;
            color: {{ $headerText }};
            font-size: 11px;
            font-weight: 950;
            line-height: 1.08;
        }

        .sigef-access-subtitle {
            max-width: 276px;
            color: {{ $headerText }};
            font-size: 10.2px;
            font-weight: 950;
            line-height: 1.08;
        }

        .sigef-access-photo {
            position: relative;
            z-index: 1;
            width: 122px;
            height: 142px;
            margin: 18px auto 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 0;
            border-radius: 16px;
            background: #dbeafe;
            color: #06145f;
            font-size: 34px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .sigef-access-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sigef-access-details {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 4px;
            margin: 13px 20px 0;
            color: #020617;
            font-size: 11.4px;
            font-weight: 600;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .sigef-access-detail-line {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .sigef-access-detail-line span {
            font-weight: 950;
        }

        .sigef-access-detail-line strong {
            color: #ff1208;
            font-size: 12.4px;
            font-weight: 600;
            line-height: 1.08;
        }

        .sigef-access-placement-row {
            display: block;
            min-width: 0;
            font-size: 9.2px;
            line-height: 1.08;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigef-access-placement-row span {
            font-weight: 950;
        }

        .sigef-access-placement-row .sigef-access-placement-separator {
            margin: 0 4px;
            font-weight: 950;
        }

        .sigef-access-role-title,
        .sigef-access-title {
            position: absolute;
            left: 18px;
            right: 18px;
            z-index: 1;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-access-role-title {
            bottom: 44px;
            color: #ff1208;
            font-size: 18px;
            font-weight: 950;
            line-height: 1;
        }

        .sigef-access-title {
            bottom: 18px;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
        }

        .sigef-access-back .sigef-access-layer {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 118px 17px 18px;
            text-align: center;
        }

        .sigef-access-back-title {
            position: relative;
            z-index: 1;
            color: #111827;
            font-size: 18px;
            font-weight: 950;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .sigef-access-back-text {
            position: relative;
            z-index: 1;
            max-width: 252px;
            margin-top: 76px;
            color: #111827;
            font-size: 9.2px;
            font-weight: 950;
            line-height: 1.34;
            white-space: pre-line;
        }

        .sigef-access-signature {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 78px;
            z-index: 1;
            display: grid;
            justify-items: center;
            gap: 2px;
            color: #111827;
            text-align: center;
            text-transform: uppercase;
        }

        .sigef-access-signature-label {
            font-size: 12px;
            font-weight: 950;
        }

        .sigef-access-signature img {
            width: 140px;
            max-height: 34px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .sigef-access-signature-name {
            font-size: 13px;
            font-weight: 950;
            line-height: 1.05;
        }

        .sigef-access-signature-title {
            font-size: 8px;
            font-weight: 950;
            line-height: 1.05;
        }

        .sigef-access-qr-wrap {
            position: absolute;
            left: 50%;
            bottom: 18px;
            z-index: 1;
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            transform: translateX(-50%);
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .16);
        }

        .sigef-access-qr-wrap img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .sigef-access-qr-fallback {
            width: 50px;
            height: 50px;
            background:
                linear-gradient(90deg, #111827 6px, transparent 6px) 0 0 / 14px 14px,
                linear-gradient(#111827 6px, transparent 6px) 0 0 / 14px 14px,
                #fff;
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

        @if (! $suppressPrintCss)
        @media print {
            @page {
                size: {{ $printWidth }} {{ $printHeight }};
                margin: 0;
            }

            html,
            body {
                width: var(--sigef-card-print-width) !important;
                height: var(--sigef-card-print-height) !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
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
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .sigef-card-preview-scene {
                width: {{ $screenWidth }} !important;
                height: {{ $screenHeight }} !important;
                perspective: none !important;
                transform: scale(var(--sigef-card-print-scale)) !important;
                transform-origin: top left !important;
                max-width: none !important;
            }

            .sigef-card-preview-stack {
                width: {{ $screenWidth }} !important;
                height: {{ $screenHeight }} !important;
                perspective: none !important;
                transform: none !important;
                transition: none !important;
            }

            .sigef-card-preview-face {
                position: absolute !important;
                inset: 0 !important;
                width: {{ $screenWidth }} !important;
                height: {{ $screenHeight }} !important;
                box-shadow: none !important;
                opacity: 1 !important;
                transform: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .sigef-card-preview-face:not(.is-active) {
                display: none !important;
            }
        }
        @endif
    </style>

    @if (! $printPageMode)
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
    @endif

    <div class="sigef-card-preview-stage">
        <div class="sigef-card-preview-scene">
            <div class="sigef-card-preview-stack" x-bind:class="{ 'is-flipped': side === 'back' }">
                @if ($usesAccessVertical)
                    @if (! $printPageMode || $showFace !== 'back')
                    <section
                        class="sigef-card-preview-face sigef-card-preview-front sigef-access-card sigef-access-front {{ $printPageMode ? 'is-active' : '' }}"
                        x-bind:class="{ 'is-active': side === 'front' }"
                        style="{{ $frontStyle }} color: {{ $frontText }};"
                    >
                        <div class="sigef-access-layer">
                            @if ($logoUrl && ! $accessFrontHasImage)
                                <img class="sigef-access-watermark sigef-access-front-watermark" src="{{ $logoUrl }}" alt="">
                            @endif

                            <header class="sigef-access-front-header">
                                @if ($logoUrl)
                                    <img class="sigef-access-logo" src="{{ $logoUrl }}" alt="Logo">
                                @endif
                                <div class="sigef-access-institution">{{ $brandName }}</div>
                                @if ($accessInstitutionSubtitle)
                                    <div class="sigef-access-subtitle">{{ $accessInstitutionSubtitle }}</div>
                                @endif
                            </header>

                            <div class="sigef-access-photo">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="Foto de {{ $name }}">
                                @endif
                            </div>

                            <div class="sigef-access-details">
                                <div class="sigef-access-detail-line">
                                    <span>NOME:</span> <strong>{{ $accessName }}</strong>
                                </div>

                                @if ($type === \App\Models\CardTemplate::TYPE_STUDENT)
                                    @foreach ($accessFrontRows->take(3) as [$label, $value])
                                        <div class="sigef-access-detail-line">
                                            <span>{{ mb_strtoupper((string) $label) }}:</span> {{ mb_strtoupper((string) $value) }}
                                        </div>
                                    @endforeach

                                    @if ($accessPlacementItems->isNotEmpty())
                                        <div class="sigef-access-placement-row">
                                            @foreach ($accessPlacementItems as [$label, $value])
                                                @if (! $loop->first)
                                                    <span class="sigef-access-placement-separator">/</span>
                                                @endif
                                                <span>{{ mb_strtoupper((string) $label) }}:</span> {{ mb_strtoupper((string) $value) }}
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (filled($academicYearValue))
                                        <div class="sigef-access-detail-line">
                                            <span>ANO ACADEMICO:</span> {{ mb_strtoupper((string) $academicYearValue) }}
                                        </div>
                                    @endif
                                @else
                                    @foreach ($accessFrontRows->take(4) as [$label, $value])
                                        <div class="sigef-access-detail-line">
                                            <span>{{ mb_strtoupper((string) $label) }}:</span> {{ mb_strtoupper((string) $value) }}
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="sigef-access-role-title">{{ $accessRoleTitle }}</div>
                            <div class="sigef-access-title">{{ $frontTitle ?: 'PASSE DE ACESSO' }}</div>
                        </div>
                    </section>
                    @endif

                    @if (! $printPageMode || $showFace !== 'front')
                    <section
                        class="sigef-card-preview-face sigef-card-preview-back sigef-access-card sigef-access-back {{ $printPageMode ? 'is-active' : '' }}"
                        x-bind:class="{ 'is-active': side === 'back' }"
                        style="{{ $backStyle }} color: {{ $backText }};"
                    >
                        <div class="sigef-access-layer">
                            <div class="sigef-access-back-title">
                                {{ $template->back_title ?: 'PRORROGATIVA' }}
                            </div>

                            <div class="sigef-access-back-text">
                                {{ $accessFooterText }}
                            </div>

                            <div class="sigef-access-signature">
                                @if ($accessSignatureLabel)
                                    <div class="sigef-access-signature-label">{{ $accessSignatureLabel }}</div>
                                @endif
                                @if (! empty($payload['signature_url']))
                                    <img src="{{ $payload['signature_url'] }}" alt="Assinatura">
                                @endif
                                @if ($accessSignatoryName)
                                    <div class="sigef-access-signature-name">{{ $accessSignatoryName }}</div>
                                @endif
                                @if ($accessSignatoryTitle)
                                    <div class="sigef-access-signature-title">** {{ $accessSignatoryTitle }} **</div>
                                @endif
                            </div>

                            @if ($showQrCode)
                                <div class="sigef-access-qr-wrap">
                                    @if (! empty($payload['qr_code_uri']))
                                        <img src="{{ $payload['qr_code_uri'] }}" alt="QR code">
                                    @else
                                        <div class="sigef-access-qr-fallback" aria-hidden="true"></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </section>
                    @endif
                @elseif ($usesHorizontalAccess)
                    @if (! $printPageMode || $showFace !== 'back')
                    <section
                        class="sigef-card-preview-face sigef-card-preview-front sigef-horizontal-card sigef-horizontal-front {{ $printPageMode ? 'is-active' : '' }}"
                        x-bind:class="{ 'is-active': side === 'front' }"
                        style="{{ $horizontalFrontStyle }} color: {{ $frontText }};"
                    >
                        <div class="sigef-horizontal-layer">
                            @if (! $horizontalHasCustomFrontBackground)
                                <div class="sigef-horizontal-corner" aria-hidden="true"></div>
                                @if ($logoUrl)
                                    <img class="sigef-horizontal-watermark" src="{{ $logoUrl }}" alt="">
                                @endif
                            @endif

                            @if ($logoUrl)
                                <img class="sigef-horizontal-logo" src="{{ $logoUrl }}" alt="Logo">
                            @endif

                            <header class="sigef-horizontal-title">
                                <span>{{ $brandName }}</span>
                                @if ($accessInstitutionSubtitle)
                                    <span class="sigef-horizontal-subtitle">{{ $accessInstitutionSubtitle }}</span>
                                @endif
                            </header>

                            <div class="sigef-horizontal-mark" aria-hidden="true">
                                <div class="sigef-horizontal-mark-letter">{{ $horizontalEntityLetter }}</div>
                                <div class="sigef-horizontal-mark-word">{{ $accessRoleTitle }}</div>
                            </div>

                            <div class="sigef-horizontal-info">
                                @foreach ($horizontalFrontRows->take(7) as [$label, $value, $highlight])
                                    @if ($label === 'NOME')
                                        <div class="sigef-horizontal-row"><strong>{{ $label }}:</strong></div>
                                        <div class="sigef-horizontal-name">{{ $value }}</div>
                                    @else
                                        <div class="sigef-horizontal-row">
                                            <strong>{{ $label }}:</strong>
                                            <span class="{{ $highlight ? 'is-highlight' : '' }}">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <div class="sigef-horizontal-photo {{ $photoUrl ? '' : 'is-empty' }}">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="Foto de {{ $name }}">
                                @endif
                            </div>
                        </div>
                    </section>
                    @endif

                    @if (! $printPageMode || $showFace !== 'front')
                    <section
                        class="sigef-card-preview-face sigef-card-preview-back sigef-horizontal-card sigef-horizontal-back {{ $printPageMode ? 'is-active' : '' }}"
                        x-bind:class="{ 'is-active': side === 'back' }"
                        style="{{ $horizontalBackStyle }} color: {{ $backText }};"
                    >
                        <div class="sigef-horizontal-layer">
                            <div class="sigef-horizontal-back-title">{{ $horizontalBackTitle }}</div>

                            <div class="sigef-horizontal-back-text">
                                {{ $horizontalBackText }}
                            </div>

                            <div class="sigef-horizontal-signature">
                                @if ($accessSignatureLabel)
                                    <div class="sigef-horizontal-signature-label">{{ $accessSignatureLabel }}</div>
                                @endif
                                @if (! empty($payload['signature_url']))
                                    <img src="{{ $payload['signature_url'] }}" alt="Assinatura">
                                @endif
                                @if ($accessSignatoryName)
                                    <div class="sigef-horizontal-signatory-name">{{ $accessSignatoryName }}</div>
                                @endif
                                @if ($accessSignatoryTitle)
                                    <div class="sigef-horizontal-signatory-title">** {{ $accessSignatoryTitle }} **</div>
                                @endif
                            </div>

                            @if ($showQrCode)
                                <div class="sigef-horizontal-qr">
                                    @if (! empty($payload['qr_code_uri']))
                                        <img src="{{ $payload['qr_code_uri'] }}" alt="QR code">
                                    @else
                                        <div class="sigef-horizontal-qr-fallback" aria-hidden="true"></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </section>
                    @endif
                @else
                @if (! $printPageMode || $showFace !== 'back')
                <section
                    class="sigef-card-preview-face sigef-card-preview-front {{ $printPageMode ? 'is-active' : '' }}"
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
                                <div class="sigef-card-preview-photo"></div>
                            @endif
                            <div class="sigef-card-preview-pill">{{ $payload['regime'] ?? $entityTitle }}</div>
                        </div>
                    </div>
                </section>
                @endif

                @if (! $printPageMode || $showFace !== 'front')
                <section
                    class="sigef-card-preview-face sigef-card-preview-back {{ $printPageMode ? 'is-active' : '' }}"
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
                @endif
                @endif
            </div>
        </div>

    @if (! $printPageMode)
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
    @endif
    </div>
</div>
