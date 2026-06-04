<x-filament-panels::page>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .report-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .dark .report-card {
            background: #1f2937;
            border-color: #374151;
        }

        .report-card h3 {
            font-size: 15px;
            font-weight: 600;
            color: #111;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dark .report-card h3 {
            color: #f3f4f6;
        }

        .report-card h3 svg {
            width: 20px;
            height: 20px;
            color: #041c4f;
            flex-shrink: 0;
        }

        .dark .report-card h3 svg {
            color: #93c5fd;
        }

        .report-card .desc {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .report-card .filters {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .report-card .filters select,
        .report-card .filters input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            background: white;
        }

        .dark .report-card .filters select,
        .dark .report-card .filters input {
            background: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }

        .report-card .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #041c4f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .report-card .btn-pdf:hover {
            opacity: 0.85;
        }

        .report-card .btn-pdf svg {
            width: 16px;
            height: 16px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #041c4f;
            margin: 24px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .dark .section-title {
            color: #93c5fd;
            border-color: #374151;
        }

        .section-title svg {
            width: 22px;
            height: 22px;
        }

        .filters-row {
            display: flex;
            gap: 8px;
        }

        .filters-row>* {
            flex: 1;
        }
    </style>

    @php
    $iconDownload = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
    </svg>';
    @endphp

    {{-- ═══════ GESTÃO ESCOLAR ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-building-library style="width:22px;height:22px" />
        Gestão Escolar
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-briefcase /> Formadores</h3>
            <p class="desc">Lista de formadores da escola</p>
            <a class="btn-pdf" onclick="openReport('trainers', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>

        <div class="report-card">
            <h3><x-heroicon-o-rectangle-stack /> Gestão de Formandos</h3>
            <p class="desc">Matrículas e inscrições por turma</p>
            <div class="filters">
                <select id="enrollments_class">
                    <option value="">Todas as Turmas</option>
                    @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('enrollments', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-wrench-screwdriver /> Atribuição de Meios</h3>
            <p class="desc">Equipamentos atribuídos a formandos</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="equipment_date_from">
                    <input type="date" id="equipment_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('equipment', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-clock /> Dispensas e Faltas</h3>
            <p class="desc">Mapa de faltas e dispensas da escola</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="leaves_date_from">
                    <input type="date" id="leaves_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('leaves', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
    </div>

    {{-- ═══════ AVALIAÇÃO ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-clipboard-document-check style="width:22px;height:22px" />
        Avaliação
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-pencil-square /> Avaliações</h3>
            <p class="desc">Relatório de avaliações por turma</p>
            <div class="filters">
                <select id="evaluations_class">
                    <option value="">Todas as Turmas</option>
                    @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('evaluations', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-document-text /> Mini Pauta</h3>
            <p class="desc">Pauta resumida por turma</p>
            <div class="filters">
                <select id="minipauta_class">
                    <option value="">Seleccione a Turma</option>
                    @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('mini-pauta', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-table-cells /> Pauta Geral</h3>
            <p class="desc">Pauta geral completa por turma</p>
            <div class="filters">
                <select id="pautageral_class">
                    <option value="">Seleccione a Turma</option>
                    @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('pauta-geral', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-trophy /> Certificados</h3>
            <p class="desc">Lista de certificados emitidos</p>
            <div class="filters">
                <select id="certificados_class">
                    <option value="">Todas as Turmas</option>
                    @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('certificados', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-check-badge /> Ponto de Presenças</h3>
            <p class="desc">Mapa de presenças por CIA (quadriculado de 30 dias)</p>
            <div class="filters">
                <select id="attendance_cia">
                    <option value="">Seleccione a CIA</option>
                    @foreach($cias as $cia)
                    <option value="{{ $cia }}">CIA {{ $cia }}</option>
                    @endforeach
                </select>
                <div class="filters-row">
                    <input type="date" id="attendance_date_from">
                    <input type="date" id="attendance_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('attendance', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
    </div>

    {{-- ═══════ DOCUMENTOS ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-folder-open style="width:22px;height:22px" />
        Documentos
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-folder-open /> Documentos</h3>
            <p class="desc">Lista de documentos enviados e recebidos</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="documents_date_from">
                    <input type="date" id="documents_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('documents', this)" href="javascript:void(0)">{!! $iconDownload !!} Baixar PDF</a>
        </div>
    </div>

    @php
    $tenantId = \Filament\Facades\Filament::getTenant()?->id;
    @endphp

    <script>
        function openReport(type, btn) {
            var params = new URLSearchParams();

            // Auto-add institution from tenant
            @if($tenantId)
            params.append('institution', '{{ $tenantId }}');
            @endif

            var filterMap = {
                'trainers': [],
                'cadetes': ['cadetes_class', 'cadetes_date_from', 'cadetes_date_to'],
                'enrollments': ['enrollments_class'],
                'equipment': ['equipment_date_from', 'equipment_date_to'],
                'transfers': ['transfers_date_from', 'transfers_date_to'],
                'leaves': ['leaves_date_from', 'leaves_date_to'],
                'evaluations': ['evaluations_class'],
                'mini-pauta': ['minipauta_class'],
                'pauta-geral': ['pautageral_class'],
                'certificados': ['certificados_class'],
                'attendance': ['attendance_cia', 'attendance_date_from', 'attendance_date_to'],
                'documents': ['documents_date_from', 'documents_date_to'],
            };
            if (filterMap[type]) {
                filterMap[type].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el && el.value) {
                        var paramName = id;
                        if (id.includes('date_from')) paramName = 'date_from';
                        else if (id.includes('date_to')) paramName = 'date_to';
                        else if (id.includes('cia')) paramName = 'cia';
                        else if (id.includes('class')) paramName = 'class';
                        else if (id.includes('year')) paramName = 'academic_year';
                        params.append(paramName, el.value);
                    }
                });
            }
            var url = '/reports/' + type;
            if (params.toString()) url += '?' + params.toString();
            window.open(url, '_blank');
        }
    </script>
</x-filament-panels::page>