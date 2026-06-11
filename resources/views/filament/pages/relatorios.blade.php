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
    // Reusable SVG icon components
    $iconDownload = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>';
    @endphp

    {{-- ═══════ GESTÃO DE ACESSO ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-users style="width:22px;height:22px" />
        Gestão de Acesso
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-user-group /> Utilizadores</h3>
            <p class="desc">Lista de todos os utilizadores do sistema</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="users_date_from" placeholder="Data Início">
                    <input type="date" id="users_date_to" placeholder="Data Fim">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('users', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-arrow-right-on-rectangle /> Acessos</h3>
            <p class="desc">Mapa de entradas e sa&iacute;das dos utilizadores no sistema</p>
            <div class="filters">
                <select id="accesses_user">
                    <option value="">Todos os Utilizadores</option>
                    @foreach($users as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <select id="accesses_action">
                    <option value="">Todos os Eventos</option>
                    <option value="login">Entradas no sistema</option>
                    <option value="logout">Sa&iacute;das do sistema</option>
                </select>
                <div class="filters-row">
                    <input type="date" id="accesses_date_from">
                    <input type="date" id="accesses_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('accesses', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-shield-check /> Auditoria</h3>
            <p class="desc">Registo documental de cria&ccedil;&otilde;es, altera&ccedil;&otilde;es e elimina&ccedil;&otilde;es</p>
            <div class="filters">
                <select id="audit_user">
                    <option value="">Todos os Utilizadores</option>
                    @foreach($users as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <select id="audit_event">
                    <option value="">Todos os Eventos</option>
                    <option value="created">Registos criados</option>
                    <option value="updated">Registos atualizados</option>
                    <option value="deleted">Registos eliminados</option>
                    <option value="restored">Registos restaurados</option>
                </select>
                <select id="audit_model">
                    <option value="">Todos os M&oacute;dulos</option>
                    @foreach($auditModels as $model => $label)
                    <option value="{{ $model }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="filters-row">
                    <input type="date" id="audit_date_from">
                    <input type="date" id="audit_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('audit', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
    </div>

    {{-- ═══════ CURRÍCULO ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-academic-cap style="width:22px;height:22px" />
        Currículo
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-map /> Mapa de Curso</h3>
            <p class="desc">Mapas de curso registados no sistema</p>
            <a class="btn-pdf" onclick="openReport('course-maps', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-clipboard-document-list /> Plano de Curso</h3>
            <p class="desc">Planos de curso e suas fases</p>
            <a class="btn-pdf" onclick="openReport('course-plans', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-academic-cap /> Cursos</h3>
            <p class="desc">Lista de cursos disponíveis</p>
            <a class="btn-pdf" onclick="openReport('courses', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-book-open /> Disciplinas</h3>
            <p class="desc">Lista de disciplinas</p>
            <a class="btn-pdf" onclick="openReport('subjects', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
    </div>

    {{-- ═══════ GESTÃO ESCOLAR ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-building-library style="width:22px;height:22px" />
        Gestão Escolar
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-briefcase /> Formadores</h3>
            <p class="desc">Lista de formadores por escola</p>
            <div class="filters">
                <select id="trainers_institution">
                    <option value="">Todas as Escolas</option>
                    @foreach($institutions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn-pdf" onclick="openReport('trainers', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-user-plus /> Alistados</h3>
            <p class="desc">Lista de candidatos alistados</p>
            <div class="filters">
                <select id="alistados_year">
                    <option value="">Todos os Anos</option>
                    @foreach($academicYears as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <div class="filters-row">
                    <input type="date" id="alistados_date_from">
                    <input type="date" id="alistados_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('alistados', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        @foreach($studentTypeReports as $report)
            @php($reportKey = 'student_type_'.$report['key'])
            <div class="report-card">
                <h3><x-heroicon-o-user-group /> {{ $report['label'] }}</h3>
                <p class="desc">{{ $report['description'] }}</p>
                <div class="filters">
                    <select id="{{ $reportKey }}_institution">
                        <option value="">Todas as Escolas</option>
                        @foreach($institutions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <select id="{{ $reportKey }}_class">
                        <option value="">Todas as Turmas</option>
                        @foreach($classes as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="filters-row">
                        <input type="date" id="{{ $reportKey }}_date_from">
                        <input type="date" id="{{ $reportKey }}_date_to">
                    </div>
                </div>
                <a class="btn-pdf" onclick='openStudentTypeReport(@json($report['filter']), @json($reportKey), this)' href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
            </div>
        @endforeach
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
            <a class="btn-pdf" onclick="openReport('enrollments', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-wrench-screwdriver /> Atribuição de Meios</h3>
            <p class="desc">Equipamentos atribuídos a formandos</p>
            <div class="filters">
                <select id="equipment_institution">
                    <option value="">Todas as Escolas</option>
                    @foreach($institutions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <div class="filters-row">
                    <input type="date" id="equipment_date_from">
                    <input type="date" id="equipment_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('equipment', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-arrows-right-left /> Histórico de Transferências</h3>
            <p class="desc">Transferências de formandos entre escolas</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="transfers_date_from">
                    <input type="date" id="transfers_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('transfers', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-clock /> Dispensas e Faltas</h3>
            <p class="desc">Mapa de faltas e dispensas por escola</p>
            <div class="filters">
                <select id="leaves_institution">
                    <option value="">Todas as Escolas</option>
                    @foreach($institutions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <div class="filters-row">
                    <input type="date" id="leaves_date_from">
                    <input type="date" id="leaves_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('leaves', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
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
            <a class="btn-pdf" onclick="openReport('evaluations', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
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
            <a class="btn-pdf" onclick="openReport('mini-pauta', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
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
            <a class="btn-pdf" onclick="openReport('pauta-geral', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
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
            <a class="btn-pdf" onclick="openReport('certificados', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-check-badge /> Ponto de Presenças</h3>
            <p class="desc">Mapa de presenças por CIA (quadriculado de 30 dias)</p>
            <div class="filters">
                <select id="attendance_institution" onchange="updateCiaOptions()">
                    <option value="">Seleccione a Escola</option>
                    @foreach($institutions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <select id="attendance_cia" disabled>
                    <option value="">Primeiro seleccione a Escola</option>
                </select>
                <div class="filters-row">
                    <input type="date" id="attendance_date_from">
                    <input type="date" id="attendance_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('attendance', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
    </div>

    {{-- ═══════ INSTITUIÇÕES & DOCUMENTOS ═══════ --}}
    <div class="section-title">
        <x-heroicon-o-building-office-2 style="width:22px;height:22px" />
        Instituições & Documentos
    </div>
    <div class="reports-grid">
        <div class="report-card">
            <h3><x-heroicon-o-building-office /> Instituições</h3>
            <p class="desc">Lista de todas as instituições de ensino</p>
            <a class="btn-pdf" onclick="openReport('institutions', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
        <div class="report-card">
            <h3><x-heroicon-o-folder-open /> Documentos</h3>
            <p class="desc">Lista de documentos do sistema</p>
            <div class="filters">
                <div class="filters-row">
                    <input type="date" id="documents_date_from">
                    <input type="date" id="documents_date_to">
                </div>
            </div>
            <a class="btn-pdf" onclick="openReport('documents', this)" href="javascript:void(0)">{!! $iconDownload !!} Pr&eacute; Visualizar</a>
        </div>
    </div>

    <script>
        var ciasGrouped = @json($ciasGrouped);

        function updateCiaOptions() {
            var instId = document.getElementById('attendance_institution').value;
            var ciaSelect = document.getElementById('attendance_cia');
            ciaSelect.innerHTML = '';

            if (!instId) {
                ciaSelect.disabled = true;
                ciaSelect.innerHTML = '<option value="">Primeiro seleccione a Escola</option>';
                return;
            }

            var cias = ciasGrouped[instId] || [];
            ciaSelect.disabled = false;
            ciaSelect.innerHTML = '<option value="">Todas as CIAs</option>';
            cias.forEach(function(cia) {
                var opt = document.createElement('option');
                opt.value = cia;
                opt.textContent = 'CIA ' + cia;
                ciaSelect.appendChild(opt);
            });
        }

        function appendReportFilters(params, filterIds) {
            filterIds.forEach(function(id) {
                var el = document.getElementById(id);
                if (el && el.value) {
                    var paramName = id;
                    if (id.includes('date_from')) paramName = 'date_from';
                    else if (id.includes('date_to')) paramName = 'date_to';
                    else if (id.includes('institution')) paramName = 'institution';
                    else if (id.includes('user')) paramName = 'user';
                    else if (id.includes('action')) paramName = 'action';
                    else if (id.includes('event')) paramName = 'event';
                    else if (id.includes('model')) paramName = 'model';
                    else if (id.includes('cia')) paramName = 'cia';
                    else if (id.includes('class')) paramName = 'class';
                    else if (id.includes('year')) paramName = 'academic_year';
                    params.append(paramName, el.value);
                }
            });
        }

        function reportTitleFromButton(btn) {
            var title = 'Relat\u00f3rio';

            if (btn) {
                var cardTitle = btn.closest('.report-card')?.querySelector('h3')?.textContent;
                if (cardTitle) {
                    title = cardTitle.replace(/\s+/g, ' ').trim();
                }
            }

            return title;
        }

        function openStudentTypeReport(studentType, prefix, btn) {
            var params = new URLSearchParams();
            params.set('student_type', studentType);
            appendReportFilters(params, [
                prefix + '_institution',
                prefix + '_class',
                prefix + '_date_from',
                prefix + '_date_to',
            ]);
            params.set('embedded', '1');
            params.set('autoprint', '0');

            @this.openReportPreview('/reports/student-types?' + params.toString(), reportTitleFromButton(btn));
        }

        function openReport(type, btn) {
            var params = new URLSearchParams();
            var filterMap = {
                'users': ['users_date_from', 'users_date_to'],
                'accesses': ['accesses_user', 'accesses_action', 'accesses_date_from', 'accesses_date_to'],
                'audit': ['audit_user', 'audit_event', 'audit_model', 'audit_date_from', 'audit_date_to'],
                'trainers': ['trainers_institution'],
                'alistados': ['alistados_year', 'alistados_date_from', 'alistados_date_to'],
                'enrollments': ['enrollments_class'],
                'equipment': ['equipment_institution', 'equipment_date_from', 'equipment_date_to'],
                'transfers': ['transfers_date_from', 'transfers_date_to'],
                'leaves': ['leaves_institution', 'leaves_date_from', 'leaves_date_to'],
                'evaluations': ['evaluations_class'],
                'mini-pauta': ['minipauta_class'],
                'pauta-geral': ['pautageral_class'],
                'certificados': ['certificados_class'],
                'attendance': ['attendance_institution', 'attendance_cia', 'attendance_date_from', 'attendance_date_to'],
                'documents': ['documents_date_from', 'documents_date_to'],
            };
            if (filterMap[type]) {
                appendReportFilters(params, filterMap[type]);
            }
            params.set('embedded', '1');
            params.set('autoprint', '0');

            var url = '/reports/' + type + '?' + params.toString();

            @this.openReportPreview(url, reportTitleFromButton(btn));
        }
    </script>
</x-filament-panels::page>
