@php
    $reportInstitution = \App\Models\SystemSetting::getReportInstitutionConfig();
    $publicAsset = function (?string $path, ?string $fallback = null): ?string {
        if (! filled($path)) {
            return $fallback;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
            : $fallback;
    };

    $logoUrl = $publicAsset($reportInstitution['logo_path'] ?? null, asset('images/logo-pna.png'));
    $photoUrl = $publicAsset($candidate->photo ?? null);
    $sheetId = 'candidate-sheet-'.$candidate->getKey();
    $documentTitle = 'Ficha do Formando - '.($candidate->full_name ?? 'SIGEF');
    $provinceName = $candidate->relationLoaded('province')
        ? ($candidate->getRelation('province')?->name ?? null)
        : null;
    $provinceName ??= $candidate->getAttribute('province');
    $municipalityName = $candidate->relationLoaded('municipalityRelation')
        ? ($candidate->getRelation('municipalityRelation')?->name ?? null)
        : null;
    $municipalityName ??= $candidate->getAttribute('municipality');
    $formatDate = function ($value): string {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $headerLines = array_filter([
        $reportInstitution['republic_line'] ?? null,
        $reportInstitution['ministry_line'] ?? null,
        $reportInstitution['organ_line'] ?? null,
        $reportInstitution['department_line'] ?? null,
    ]);
    $footerContacts = array_filter([
        $reportInstitution['address'] ?? null,
        ! empty($reportInstitution['phone']) ? 'Tel.: '.$reportInstitution['phone'] : null,
        $reportInstitution['email'] ?? null,
        $reportInstitution['website'] ?? null,
    ]);
    $footerText = $reportInstitution['footer_text']
        ?: implode(' | ', array_filter([$reportInstitution['name'] ?? 'SIGEF', ...$footerContacts]));
    $identityRows = [
        ['Nome completo', $candidate->full_name],
        ['Nº do BI', $candidate->id_number],
        ['Data de nascimento', $formatDate($candidate->birth_date)],
        ['Género', $candidate->gender],
        ['Estado civil', $candidate->marital_status],
        ['Nome do pai', $candidate->father_name],
        ['Nome da mãe', $candidate->mother_name],
        ['NURI/NIP', $candidate->nuri],
    ];
    $contactRows = [
        ['Telefone', $candidate->phone],
        ['E-mail', $candidate->email],
        ['Província', $provinceName],
        ['Município', $municipalityName],
        ['Endereço', $candidate->address],
    ];
    $classificationRows = [
        ['Tipo', $candidate->student_type],
        ['Resultado', $candidate->status],
        ['Instituição atribuída', $candidate->institution?->name],
        ['Tipo de recrutamento', $candidate->recruitmentType?->name],
        ['Proveniência', $candidate->provenance?->name],
        ['Ano académico', $candidate->academicYear?->name],
        ['Nível de ensino', $candidate->education_level],
        ['Área de formação', $candidate->education_area],
    ];
@endphp

<style id="{{ $sheetId }}-styles">
    .sigef-candidate-sheet {
        background: #ffffff;
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        max-width: 920px;
        margin: 0 auto;
        padding: 28px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
    }

    .sigef-candidate-sheet-actions {
        max-width: 920px;
        margin: 0 auto 14px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .sigef-candidate-print-button {
        border: 0;
        background: #041c4f;
        color: #ffffff;
        border-radius: 6px;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .sigef-candidate-sheet-header {
        display: grid;
        grid-template-columns: 96px 1fr 96px;
        gap: 18px;
        align-items: center;
        border-bottom: 3px solid #041c4f;
        padding-bottom: 18px;
        margin-bottom: 18px;
    }

    .sigef-candidate-sheet-logo,
    .sigef-candidate-sheet-photo {
        width: 88px;
        height: 88px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #f8fafc;
        color: #041c4f;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .sigef-candidate-sheet-logo img,
    .sigef-candidate-sheet-photo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .sigef-candidate-sheet-photo img {
        object-fit: cover;
    }

    .sigef-candidate-sheet-heading {
        text-align: center;
    }

    .sigef-candidate-sheet-lines {
        color: #041c4f;
        font-size: 11px;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .sigef-candidate-sheet-institution {
        margin-top: 8px;
        color: #041c4f;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sigef-candidate-sheet-title {
        margin-top: 8px;
        color: #111827;
        font-size: 18px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sigef-candidate-sheet-meta {
        margin-top: 5px;
        color: #6b7280;
        font-size: 11px;
    }

    .sigef-candidate-status-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .sigef-candidate-status-box {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 9px 10px;
        background: #f8fafc;
    }

    .sigef-candidate-label {
        display: block;
        color: #6b7280;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .sigef-candidate-value {
        color: #111827;
        font-size: 12px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .sigef-candidate-section {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        overflow: hidden;
        margin-top: 14px;
    }

    .sigef-candidate-section-title {
        background: #041c4f;
        color: #ffffff;
        padding: 9px 12px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .sigef-candidate-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0;
    }

    .sigef-candidate-cell {
        min-height: 58px;
        padding: 10px 12px;
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }

    .sigef-candidate-cell:nth-child(3n) {
        border-right: 0;
    }

    .sigef-candidate-signatures {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 80px;
        margin-top: 58px;
        padding: 0 22px;
    }

    .sigef-candidate-signature-line {
        border-top: 1px solid #111827;
        padding-top: 7px;
        text-align: center;
        color: #4b5563;
        font-size: 11px;
        font-weight: 700;
    }

    .sigef-candidate-footer {
        margin-top: 32px;
        padding-top: 10px;
        border-top: 1px solid #d1d5db;
        color: #6b7280;
        font-size: 10px;
        text-align: center;
        line-height: 1.4;
    }

    @media (max-width: 760px) {
        .sigef-candidate-sheet {
            padding: 16px;
        }

        .sigef-candidate-sheet-header,
        .sigef-candidate-status-row,
        .sigef-candidate-grid,
        .sigef-candidate-signatures {
            grid-template-columns: 1fr;
        }

        .sigef-candidate-cell,
        .sigef-candidate-cell:nth-child(3n) {
            border-right: 0;
        }

        .sigef-candidate-sheet-logo,
        .sigef-candidate-sheet-photo {
            margin: 0 auto;
        }
    }

    @media print {
        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            background: #ffffff;
            margin: 0;
        }

        body * {
            visibility: hidden !important;
        }

        .sigef-candidate-sheet {
            max-width: none;
            border: 0;
            border-radius: 0;
            padding: 0;
            position: absolute;
            top: 0;
            left: 0;
            width: 100% !important;
            visibility: visible !important;
        }

        .sigef-candidate-sheet * {
            visibility: visible !important;
        }

        .no-print {
            display: none !important;
        }
    }
</style>

<div class="sigef-candidate-sheet-actions no-print">
    <button
        type="button"
        class="sigef-candidate-print-button"
        onclick="window.print()">
        Imprimir Ficha
    </button>
</div>

<article id="{{ $sheetId }}" class="sigef-candidate-sheet">
    <header class="sigef-candidate-sheet-header">
        <div class="sigef-candidate-sheet-logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logotipo">
            @else
                SIGEF
            @endif
        </div>

        <div class="sigef-candidate-sheet-heading">
            @if(count($headerLines) > 0)
                <div class="sigef-candidate-sheet-lines">
                    @foreach($headerLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif
            <div class="sigef-candidate-sheet-institution">{{ $reportInstitution['name'] ?? 'SIGEF' }}</div>
            <div class="sigef-candidate-sheet-title">Ficha do Formando</div>
            <div class="sigef-candidate-sheet-meta">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="sigef-candidate-sheet-photo">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Foto do formando">
            @else
                Sem foto
            @endif
        </div>
    </header>

    <div class="sigef-candidate-status-row">
        <div class="sigef-candidate-status-box">
            <span class="sigef-candidate-label">Código</span>
            <span class="sigef-candidate-value">FORM-{{ str_pad((string) $candidate->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="sigef-candidate-status-box">
            <span class="sigef-candidate-label">Tipo</span>
            <span class="sigef-candidate-value">{{ $candidate->student_type ?: '-' }}</span>
        </div>
        <div class="sigef-candidate-status-box">
            <span class="sigef-candidate-label">Resultado</span>
            <span class="sigef-candidate-value">{{ $candidate->status ?: '-' }}</span>
        </div>
        <div class="sigef-candidate-status-box">
            <span class="sigef-candidate-label">Data de registo</span>
            <span class="sigef-candidate-value">{{ $formatDate($candidate->created_at) }}</span>
        </div>
    </div>

    <section class="sigef-candidate-section">
        <div class="sigef-candidate-section-title">Identificação pessoal</div>
        <div class="sigef-candidate-grid">
            @foreach($identityRows as [$label, $value])
                <div class="sigef-candidate-cell">
                    <span class="sigef-candidate-label">{{ $label }}</span>
                    <span class="sigef-candidate-value">{{ filled($value) ? $value : '-' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="sigef-candidate-section">
        <div class="sigef-candidate-section-title">Localização e contacto</div>
        <div class="sigef-candidate-grid">
            @foreach($contactRows as [$label, $value])
                <div class="sigef-candidate-cell">
                    <span class="sigef-candidate-label">{{ $label }}</span>
                    <span class="sigef-candidate-value">{{ filled($value) ? $value : '-' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="sigef-candidate-section">
        <div class="sigef-candidate-section-title">Classificação e enquadramento</div>
        <div class="sigef-candidate-grid">
            @foreach($classificationRows as [$label, $value])
                <div class="sigef-candidate-cell">
                    <span class="sigef-candidate-label">{{ $label }}</span>
                    <span class="sigef-candidate-value">{{ filled($value) ? $value : '-' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="sigef-candidate-signatures">
        <div class="sigef-candidate-signature-line">Assinatura do Formando</div>
        <div class="sigef-candidate-signature-line">
            {{ $reportInstitution['director_title'] ?: 'Responsável' }}
            @if(! empty($reportInstitution['director_name']))
                <br>{{ $reportInstitution['director_name'] }}
            @endif
        </div>
    </div>

    <footer class="sigef-candidate-footer">
        {{ $footerText ?: 'SIGEF' }}
    </footer>
</article>
