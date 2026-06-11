<x-filament-panels::page>
    <style>
        .sigef-full-header {
            display: none !important;
        }

        .mini-pauta-page {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .mini-pauta-filter-card,
        .mini-pauta-empty-card {
            border: 1px solid #d8dee9;
            border-radius: 0.5rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .dark .mini-pauta-filter-card,
        .dark .mini-pauta-empty-card {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
        }

        .mini-pauta-filter-card {
            display: grid;
            grid-template-columns: 8rem minmax(0, 1fr) 12rem;
            gap: 1.75rem;
            align-items: center;
            padding: 1.65rem 2rem;
        }

        .mini-pauta-logo {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mini-pauta-logo img {
            width: 5.75rem;
            height: 5.75rem;
            object-fit: contain;
        }

        .mini-pauta-title {
            margin: 0 0 1.25rem;
            color: #041B4E;
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dark .mini-pauta-title {
            color: #ffffff;
        }

        .mini-pauta-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(16rem, 1fr));
            gap: 0.85rem 1.35rem;
        }

        .mini-pauta-field {
            display: grid;
            grid-template-columns: 7rem minmax(0, 1fr);
            gap: 0.75rem;
            align-items: center;
        }

        .mini-pauta-field label {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .dark .mini-pauta-field label {
            color: #f8fafc;
        }

        .mini-pauta-select {
            width: 100%;
            min-height: 2.35rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.45rem;
            background: #ffffff;
            color: #111827;
            font-size: 0.88rem;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .dark .mini-pauta-select {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
            color: #ffffff;
        }

        .mini-pauta-select:disabled {
            cursor: not-allowed;
            opacity: 0.62;
        }

        .mini-pauta-selectbox {
            position: relative;
            min-width: 0;
        }

        .mini-pauta-select-button {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            min-height: 2.35rem;
            padding: 0.5rem 0.62rem 0.5rem 0.7rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.45rem;
            background: #ffffff;
            color: #111827;
            font-size: 0.88rem;
            line-height: 1.25;
            text-align: left;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .mini-pauta-select-button:hover,
        .mini-pauta-select-button.is-open {
            border-color: #1a48ab;
            box-shadow: 0 0 0 2px rgba(26, 72, 171, 0.14);
        }

        .mini-pauta-select-button:disabled {
            cursor: not-allowed;
            border-color: #d1d5db;
            background: #f8fafc;
            opacity: 0.62;
        }

        .dark .mini-pauta-select-button {
            border-color: #4b5563;
            background: rgb(var(--gray-950));
            color: #ffffff;
        }

        .mini-pauta-select-value {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mini-pauta-select-placeholder {
            color: rgb(var(--gray-400));
        }

        .mini-pauta-select-chevron {
            width: 1rem;
            height: 1rem;
            color: rgb(var(--gray-500));
            transition: transform 0.15s ease;
        }

        .mini-pauta-select-button.is-open .mini-pauta-select-chevron {
            transform: rotate(180deg);
        }

        .mini-pauta-select-clear {
            position: absolute;
            top: 50%;
            right: 2rem;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.2rem;
            height: 1.2rem;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: rgb(var(--gray-400));
            transform: translateY(-50%);
            cursor: pointer;
        }

        .mini-pauta-select-clear::before {
            content: "\00d7";
            font-size: 1.08rem;
            font-weight: 700;
            line-height: 1;
        }

        .mini-pauta-select-clear:hover {
            color: #041B4E;
        }

        .mini-pauta-select-panel {
            position: absolute;
            top: calc(100% + 0.25rem);
            left: 0;
            right: 0;
            z-index: 60;
            overflow: hidden;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.55rem;
            background: #ffffff;
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.16);
        }

        .dark .mini-pauta-select-panel {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
        }

        .mini-pauta-select-search {
            width: calc(100% - 1rem);
            margin: 0.5rem;
            min-height: 2.15rem;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.4rem;
            color: #111827;
            font-size: 0.85rem;
        }

        .dark .mini-pauta-select-search {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
            color: #ffffff;
        }

        .mini-pauta-select-options {
            max-height: 14rem;
            overflow-y: auto;
            padding: 0.25rem;
        }

        .mini-pauta-select-option,
        .mini-pauta-select-empty {
            width: 100%;
            border: 0;
            border-radius: 0.35rem;
            padding: 0.55rem 0.65rem;
            background: transparent;
            color: #111827;
            font-size: 0.85rem;
            text-align: left;
        }

        .dark .mini-pauta-select-option,
        .dark .mini-pauta-select-empty {
            color: #ffffff;
        }

        .mini-pauta-select-option:hover,
        .mini-pauta-select-option.is-selected {
            background: rgba(26, 72, 171, 0.1);
            color: #041B4E;
        }

        .dark .mini-pauta-select-option:hover,
        .dark .mini-pauta-select-option.is-selected {
            background: rgba(96, 165, 250, 0.16);
            color: #ffffff;
        }

        .mini-pauta-select-empty {
            color: rgb(var(--gray-500));
        }

        .mini-pauta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1rem;
            padding-left: 7.75rem;
        }

        .mini-pauta-photo-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.7rem;
        }

        .mini-pauta-photo-frame {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 9.5rem;
            height: 10.25rem;
            overflow: hidden;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.75rem;
            background: rgb(var(--gray-50));
            color: #041B4E;
            padding: 0;
        }

        .dark .mini-pauta-photo-frame {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
            color: #ffffff;
        }

        .mini-pauta-photo-trigger {
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .mini-pauta-photo-trigger:hover {
            border-color: #041B4E;
            box-shadow: 0 14px 30px rgba(4, 27, 78, 0.16);
            transform: translateY(-1px);
        }

        .mini-pauta-photo-trigger:focus-visible {
            outline: 3px solid rgba(26, 72, 171, 0.28);
            outline-offset: 3px;
        }

        .mini-pauta-photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mini-pauta-photo-frame svg {
            width: 4rem;
            height: 4rem;
        }

        .mini-pauta-photo-initials {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 0;
        }

        .mini-pauta-photo-avatar.fi-avatar {
            width: 5.75rem;
            height: 5.75rem;
        }

        .mini-pauta-photo-name {
            max-width: 11rem;
            color: rgb(var(--gray-600));
            font-size: 0.78rem;
            font-weight: 800;
            line-height: 1.2;
            text-align: center;
        }

        .dark .mini-pauta-photo-name {
            color: rgb(var(--gray-300));
        }

        .mini-pauta-page [x-cloak] {
            display: none !important;
        }

        .mini-pauta-photo-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.72);
        }

        .mini-pauta-photo-modal-card {
            position: relative;
            width: min(92vw, 34rem);
            max-height: min(90vh, 44rem);
            overflow: hidden;
            border-radius: 0.55rem;
            background: #ffffff;
            padding: 1.1rem 1.1rem 1.25rem;
            box-shadow: 0 26px 70px rgba(2, 6, 23, 0.35);
        }

        .dark .mini-pauta-photo-modal-card {
            background: rgb(var(--gray-900));
        }

        .mini-pauta-photo-modal-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border: 0;
            border-radius: 0.4rem;
            background: #041B4E;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1;
            cursor: pointer;
        }

        .mini-pauta-photo-modal-close:hover,
        .mini-pauta-photo-modal-close:focus-visible {
            background: #1a48ab;
            outline: none;
        }

        .mini-pauta-photo-modal-image {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 22rem;
            overflow: hidden;
            background: rgb(var(--gray-50));
        }

        .dark .mini-pauta-photo-modal-image {
            background: rgb(var(--gray-950));
        }

        .mini-pauta-photo-modal-image img:not(.fi-avatar) {
            width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }

        .mini-pauta-photo-modal-avatar.fi-avatar {
            width: 16rem;
            height: 16rem;
        }

        .mini-pauta-photo-modal-name {
            padding-top: 1rem;
            color: #041B4E;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
            text-align: center;
            text-transform: uppercase;
        }

        .dark .mini-pauta-photo-modal-name {
            color: #ffffff;
        }

        .mini-pauta-empty-card {
            padding: 1.35rem 1.75rem;
            color: rgb(var(--gray-600));
            font-size: 0.92rem;
        }

        .mini-pauta-table {
            margin-top: 0.25rem;
        }

        @media (max-width: 1100px) {
            .mini-pauta-filter-card {
                grid-template-columns: 1fr;
            }

            .mini-pauta-logo {
                justify-content: flex-start;
            }

            .mini-pauta-actions {
                padding-left: 0;
            }

            .mini-pauta-photo-panel {
                align-items: flex-start;
            }
        }

        @media (max-width: 760px) {
            .mini-pauta-filter-card {
                padding: 1rem;
            }

            .mini-pauta-fields,
            .mini-pauta-field {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('miniPautaSearchSelect', (config) => ({
                    options: config.options || [],
                    value: config.value,
                    placeholder: config.placeholder || 'Selecione...',
                    searchPlaceholder: config.searchPlaceholder || 'Pesquisar...',
                    disabled: Boolean(config.disabled),
                    search: '',
                    open: false,

                    init() {
                        this.syncSearch();
                        this.$watch('value', () => this.syncSearch());
                    },

                    get selectedOption() {
                        return this.options.find((option) => String(option.value) === String(this.value));
                    },

                    get selectedLabel() {
                        return this.selectedOption ? this.selectedOption.label : '';
                    },

                    get filteredOptions() {
                        const term = this.normalize(this.search);

                        if (!term) {
                            return this.options;
                        }

                        return this.options.filter((option) => this.normalize(option.label).includes(term));
                    },

                    normalize(value) {
                        return String(value || '')
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '');
                    },

                    toggle() {
                        if (this.disabled) {
                            return;
                        }

                        this.open = !this.open;

                        if (this.open) {
                            this.search = '';
                            this.$nextTick(() => this.$refs.searchInput?.focus());
                        }
                    },

                    close() {
                        this.open = false;
                        this.syncSearch();
                    },

                    choose(option) {
                        this.value = option.value;
                        this.search = option.label;
                        this.open = false;
                    },

                    clear() {
                        this.value = null;
                        this.search = '';
                        this.open = false;
                    },

                    syncSearch() {
                        this.search = this.selectedLabel;
                    },
                }));
            });
        </script>
    @endonce

    @php
        $tenant = \Filament\Facades\Filament::getTenant();
        $photoUrl = $this->getSelectedStudentPhotoUrl();
        $studentName = $this->getSelectedStudentName();
        $defaultAvatarUrl = $studentName
            ? 'https://ui-avatars.com/api/?name=' . rawurlencode($studentName) . '&background=000000&color=ffffff&size=256&font-size=0.4&bold=true'
            : null;

        $institutionOptions = $this->getInstitutions()
            ->map(fn ($institution) => ['value' => (int) $institution->id, 'label' => (string) $institution->name])
            ->values()
            ->all();
        $academicYearOptions = $this->getAcademicYears()
            ->map(fn ($academicYear) => ['value' => (int) $academicYear->id, 'label' => (string) ($academicYear->year ?: $academicYear->name)])
            ->values()
            ->all();
        $courseOptions = $this->getCourses()
            ->map(fn ($course) => ['value' => (int) $course->id, 'label' => (string) $course->name])
            ->values()
            ->all();
        $classOptions = $this->getClasses()
            ->map(fn ($class) => ['value' => (int) $class->id, 'label' => (string) $class->name])
            ->values()
            ->all();
        $subjectOptions = $this->getSubjects()
            ->map(fn ($subject) => ['value' => (int) $subject->id, 'label' => (string) $subject->name])
            ->values()
            ->all();
    @endphp

    <div class="mini-pauta-page" x-data="{ photoModalOpen: false }" x-on:keydown.escape.window="photoModalOpen = false">
        <div class="mini-pauta-filter-card">
            <div class="mini-pauta-logo">
                <img src="{{ $this->getInstitutionLogoUrl() }}" alt="Logo da instituição">
            </div>

            <div>
                <h2 class="mini-pauta-title">Mini Pauta do Professor</h2>

                <div class="mini-pauta-fields">
                    <div class="mini-pauta-field">
                        <label for="mini-pauta-institution">Instituição</label>
                        <div
                            wire:key="mini-pauta-institution-{{ count($institutionOptions) }}-{{ $this->institution_id ?: 'none' }}"
                            id="mini-pauta-institution"
                            class="mini-pauta-selectbox"
                            x-data="miniPautaSearchSelect({ options: @js($institutionOptions), value: @entangle('institution_id').live, placeholder: 'Seleccione a instituição...', searchPlaceholder: 'Pesquisar instituição...', disabled: {{ $tenant ? 'true' : 'false' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="mini-pauta-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="mini-pauta-select-value" x-bind:class="{ 'mini-pauta-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="mini-pauta-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="mini-pauta-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Instituição"></button>
                            <div class="mini-pauta-select-panel" x-show="open" x-transition>
                                <input x-ref="searchInput" class="mini-pauta-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="mini-pauta-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="mini-pauta-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="mini-pauta-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-pauta-field">
                        <label for="mini-pauta-academic-year">Ano Lectivo</label>
                        <div
                            wire:key="mini-pauta-academic-year-{{ $this->institution_id ?: 'none' }}-{{ count($academicYearOptions) }}"
                            id="mini-pauta-academic-year"
                            class="mini-pauta-selectbox"
                            x-data="miniPautaSearchSelect({ options: @js($academicYearOptions), value: @entangle('academic_year_id').live, placeholder: '{{ $this->institution_id ? 'Seleccione o ano lectivo...' : 'Seleccione a instituição primeiro' }}', searchPlaceholder: 'Pesquisar ano lectivo...', disabled: {{ $this->institution_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="mini-pauta-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="mini-pauta-select-value" x-bind:class="{ 'mini-pauta-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="mini-pauta-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="mini-pauta-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Ano Lectivo"></button>
                            <div class="mini-pauta-select-panel" x-show="open" x-transition>
                                <input x-ref="searchInput" class="mini-pauta-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="mini-pauta-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="mini-pauta-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="mini-pauta-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-pauta-field">
                        <label for="mini-pauta-course">Curso</label>
                        <div
                            wire:key="mini-pauta-course-{{ $this->institution_id ?: 'none' }}-{{ $this->academic_year_id ?: 'none' }}-{{ count($courseOptions) }}"
                            id="mini-pauta-course"
                            class="mini-pauta-selectbox"
                            x-data="miniPautaSearchSelect({ options: @js($courseOptions), value: @entangle('course_id').live, placeholder: '{{ $this->academic_year_id ? 'Seleccione o curso...' : 'Seleccione o ano primeiro' }}', searchPlaceholder: 'Pesquisar curso...', disabled: {{ $this->academic_year_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="mini-pauta-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="mini-pauta-select-value" x-bind:class="{ 'mini-pauta-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="mini-pauta-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="mini-pauta-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Curso"></button>
                            <div class="mini-pauta-select-panel" x-show="open" x-transition>
                                <input x-ref="searchInput" class="mini-pauta-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="mini-pauta-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="mini-pauta-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="mini-pauta-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-pauta-field">
                        <label for="mini-pauta-class">Turma</label>
                        <div
                            wire:key="mini-pauta-class-{{ $this->institution_id ?: 'none' }}-{{ $this->academic_year_id ?: 'none' }}-{{ $this->course_id ?: 'none' }}-{{ count($classOptions) }}"
                            id="mini-pauta-class"
                            class="mini-pauta-selectbox"
                            x-data="miniPautaSearchSelect({ options: @js($classOptions), value: @entangle('class_id').live, placeholder: '{{ $this->course_id ? 'Seleccione a turma...' : 'Seleccione o curso primeiro' }}', searchPlaceholder: 'Pesquisar turma...', disabled: {{ $this->course_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="mini-pauta-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="mini-pauta-select-value" x-bind:class="{ 'mini-pauta-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="mini-pauta-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="mini-pauta-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Turma"></button>
                            <div class="mini-pauta-select-panel" x-show="open" x-transition>
                                <input x-ref="searchInput" class="mini-pauta-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="mini-pauta-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="mini-pauta-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="mini-pauta-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-pauta-field">
                        <label for="mini-pauta-subject">Disciplina</label>
                        <div
                            wire:key="mini-pauta-subject-{{ $this->class_id ?: 'none' }}-{{ count($subjectOptions) }}"
                            id="mini-pauta-subject"
                            class="mini-pauta-selectbox"
                            x-data="miniPautaSearchSelect({ options: @js($subjectOptions), value: @entangle('subject_id').live, placeholder: '{{ $this->class_id ? 'Seleccione a disciplina...' : 'Seleccione a turma primeiro' }}', searchPlaceholder: 'Pesquisar disciplina...', disabled: {{ $this->class_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="mini-pauta-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="mini-pauta-select-value" x-bind:class="{ 'mini-pauta-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="mini-pauta-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="mini-pauta-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Disciplina"></button>
                            <div class="mini-pauta-select-panel" x-show="open" x-transition>
                                <input x-ref="searchInput" class="mini-pauta-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="mini-pauta-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="mini-pauta-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="mini-pauta-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mini-pauta-actions">
                    <x-filament::button
                        wire:click="pesquisar"
                        icon="heroicon-o-magnifying-glass"
                        :disabled="!$this->institution_id || !$this->academic_year_id || !$this->course_id || !$this->class_id || !$this->subject_id"
                    >
                        Pesquisar
                    </x-filament::button>

                    @if($this->showTable && $this->class_id && $this->subject_id)
                        {{ $this->printMiniPautaAction }}
                    @endif
                </div>
            </div>

            <div class="mini-pauta-photo-panel">
                <button
                    type="button"
                    @class([
                        'mini-pauta-photo-frame',
                        'mini-pauta-photo-trigger' => filled($studentName),
                    ])
                    @if($studentName)
                        x-on:click="photoModalOpen = true"
                        aria-label="Ver foto de {{ $studentName }}"
                    @else
                        disabled
                    @endif
                >
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto de {{ $studentName }}">
                    @elseif($defaultAvatarUrl)
                        <x-filament::avatar
                            :src="$defaultAvatarUrl"
                            :alt="'Avatar de ' . $studentName"
                            size="lg"
                            class="mini-pauta-photo-avatar"
                        />
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                        </svg>
                    @endif
                </button>
                <div class="mini-pauta-photo-name">
                    {{ $studentName ?: 'Foto do aluno' }}
                </div>
            </div>
        </div>

        @if($this->showTable)
            <div class="mini-pauta-table">
                {{ $this->table }}
            </div>
        @else
            <div class="mini-pauta-empty-card">
                Aguardando selecção dos filtros da mini pauta.
            </div>
        @endif

        @if($studentName)
            <div
                x-cloak
                x-show="photoModalOpen"
                x-transition.opacity
                class="mini-pauta-photo-modal"
                role="dialog"
                aria-modal="true"
                aria-label="Foto de {{ $studentName }}"
                x-on:click.self="photoModalOpen = false"
            >
                <div class="mini-pauta-photo-modal-card">
                    <button type="button" class="mini-pauta-photo-modal-close" x-on:click="photoModalOpen = false" aria-label="Fechar">
                        X
                    </button>

                    <div class="mini-pauta-photo-modal-image">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Foto de {{ $studentName }}">
                        @elseif($defaultAvatarUrl)
                            <x-filament::avatar
                                :src="$defaultAvatarUrl"
                                :alt="'Avatar de ' . $studentName"
                                size="lg"
                                class="mini-pauta-photo-modal-avatar"
                            />
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" fill="none" viewBox="0 0 24 24" stroke="#041B4E" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                            </svg>
                        @endif
                    </div>

                    <div class="mini-pauta-photo-modal-name">
                        {{ $studentName }}
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
