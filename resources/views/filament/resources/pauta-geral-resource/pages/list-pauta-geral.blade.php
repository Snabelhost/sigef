<x-filament-panels::page>
    <style>
        .sigef-full-header {
            display: none !important;
        }

        .pauta-geral-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .pauta-geral-filter-card,
        .pauta-geral-legend-card {
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            border: 1px solid #d8dee9;
        }

        .dark .pauta-geral-filter-card,
        .dark .pauta-geral-legend-card {
            background: rgb(var(--gray-900));
            border-color: rgba(255, 255, 255, 0.1);
        }

        .pauta-geral-filter-card {
            display: grid;
            grid-template-columns: 120px minmax(0, 1fr);
            gap: 2rem;
            padding: 2rem 2.25rem;
        }

        .pauta-geral-logo {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .pauta-geral-logo img {
            width: 110px;
            height: 110px;
            object-fit: contain;
        }

        .pauta-geral-title {
            margin: 0 0 1.35rem;
            color: #041B4E;
            font-size: 1.1rem;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dark .pauta-geral-title {
            color: #ffffff;
        }

        .pauta-geral-fields {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .pauta-geral-field label {
            display: block;
            margin-bottom: 0.45rem;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .dark .pauta-geral-field label {
            color: rgb(var(--gray-100));
        }

        .pauta-geral-selectbox {
            position: relative;
        }

        .pauta-geral-select-button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            min-height: 2.6rem;
            padding: 0.55rem 0.8rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.55rem;
            background: #ffffff;
            color: #111827;
            font-size: 0.92rem;
            text-align: left;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .pauta-geral-select-button:hover,
        .pauta-geral-select-button.is-open {
            border-color: #041B4E;
            box-shadow: 0 0 0 3px rgba(4, 27, 78, 0.1);
        }

        .pauta-geral-select-button:disabled {
            cursor: not-allowed;
            border-color: #d1d5db;
            background: #f8fafc;
            opacity: 0.72;
        }

        .dark .pauta-geral-select-button {
            border-color: #4b5563;
            background: rgb(var(--gray-800));
            color: #ffffff;
        }

        .pauta-geral-select-value {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pauta-geral-select-placeholder {
            color: rgb(var(--gray-500));
        }

        .pauta-geral-select-chevron {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            color: rgb(var(--gray-500));
            transition: transform 0.2s ease;
        }

        .pauta-geral-select-button.is-open .pauta-geral-select-chevron {
            transform: rotate(180deg);
        }

        .pauta-geral-select-clear {
            position: absolute;
            top: 50%;
            right: 2rem;
            width: 1.35rem;
            height: 1.35rem;
            transform: translateY(-50%);
            border: 0;
            border-radius: 999px;
            color: rgb(var(--gray-500));
            cursor: pointer;
        }

        .pauta-geral-select-clear::before {
            content: "x";
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1;
        }

        .pauta-geral-select-clear:hover {
            color: #dc2626;
        }

        .pauta-geral-select-panel {
            position: absolute;
            z-index: 30;
            top: calc(100% + 0.4rem);
            left: 0;
            right: 0;
            overflow: hidden;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.65rem;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        }

        .dark .pauta-geral-select-panel {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
        }

        .pauta-geral-select-search {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 0;
            border-bottom: 1px solid rgb(var(--gray-200));
            background: transparent;
            color: inherit;
            font-size: 0.9rem;
            outline: none;
        }

        .dark .pauta-geral-select-search {
            border-bottom-color: rgb(var(--gray-700));
        }

        .pauta-geral-select-options {
            max-height: 14rem;
            overflow-y: auto;
            padding: 0.35rem;
        }

        .pauta-geral-select-option,
        .pauta-geral-select-empty {
            display: block;
            width: 100%;
            padding: 0.55rem 0.65rem;
            border: 0;
            border-radius: 0.45rem;
            background: transparent;
            color: #111827;
            font-size: 0.9rem;
            text-align: left;
        }

        .dark .pauta-geral-select-option,
        .dark .pauta-geral-select-empty {
            color: rgb(var(--gray-100));
        }

        .pauta-geral-select-option:hover,
        .pauta-geral-select-option.is-selected {
            background: rgba(4, 27, 78, 0.08);
            color: #041B4E;
        }

        .dark .pauta-geral-select-option:hover,
        .dark .pauta-geral-select-option.is-selected {
            background: rgba(96, 165, 250, 0.16);
            color: #ffffff;
        }

        .pauta-geral-select-empty {
            color: rgb(var(--gray-500));
        }

        .pauta-geral-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1.2rem;
        }

        .pauta-geral-legend-card {
            padding: 1rem 1.25rem;
        }

        .pauta-geral-legend-title {
            margin: 0 0 0.7rem;
            color: #041B4E;
            font-size: 0.9rem;
            font-weight: 900;
        }

        .dark .pauta-geral-legend-title {
            color: #ffffff;
        }

        .pauta-geral-legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            color: #374151;
            font-size: 0.78rem;
        }

        .dark .pauta-geral-legend-items {
            color: rgb(var(--gray-300));
        }

        .pauta-geral-page [x-cloak] {
            display: none !important;
        }

        @media (max-width: 1200px) {
            .pauta-geral-fields {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .pauta-geral-filter-card {
                grid-template-columns: 1fr;
                padding: 1.25rem;
            }

            .pauta-geral-logo {
                justify-content: flex-start;
            }

            .pauta-geral-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('pautaGeralSearchSelect', (config) => ({
                    options: config.options || [],
                    value: config.value,
                    placeholder: config.placeholder || 'Seleccione uma opção',
                    searchPlaceholder: config.searchPlaceholder || 'Pesquisar...',
                    disabled: Boolean(config.disabled),
                    open: false,
                    search: '',

                    get selectedOption() {
                        return this.options.find((option) => String(option.value) === String(this.value));
                    },

                    get selectedLabel() {
                        return this.selectedOption ? this.selectedOption.label : '';
                    },

                    get filteredOptions() {
                        const term = this.search.trim().toLowerCase();

                        if (! term) {
                            return this.options;
                        }

                        return this.options.filter((option) => option.label.toLowerCase().includes(term));
                    },

                    init() {
                        this.syncSearch();
                    },

                    toggle() {
                        if (this.disabled) {
                            return;
                        }

                        this.open = ! this.open;

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
    @endphp

    <div class="pauta-geral-page">
        <div class="pauta-geral-filter-card">
            <div class="pauta-geral-logo">
                <img src="{{ $this->getInstitutionLogoUrl() }}" alt="Logo da instituição">
            </div>

            <div>
                <h2 class="pauta-geral-title">Pauta Geral de Avaliações</h2>

                <div class="pauta-geral-fields">
                    <div class="pauta-geral-field">
                        <label for="pauta-geral-institution">Instituição</label>
                        <div
                            wire:key="pauta-geral-institution-{{ count($institutionOptions) }}-{{ $this->institution_id ?: 'none' }}"
                            id="pauta-geral-institution"
                            class="pauta-geral-selectbox"
                            x-data="pautaGeralSearchSelect({ options: @js($institutionOptions), value: @entangle('institution_id').live, placeholder: 'Seleccione a instituição...', searchPlaceholder: 'Pesquisar instituição...', disabled: {{ $tenant ? 'true' : 'false' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="pauta-geral-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="pauta-geral-select-value" x-bind:class="{ 'pauta-geral-select-placeholder': ! selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="pauta-geral-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="pauta-geral-select-clear" x-show="value !== null && value !== '' && ! disabled" x-on:click.stop="clear()" aria-label="Limpar Instituição"></button>
                            <div class="pauta-geral-select-panel" x-cloak x-show="open" x-transition>
                                <input x-ref="searchInput" class="pauta-geral-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="pauta-geral-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="pauta-geral-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="pauta-geral-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pauta-geral-field">
                        <label for="pauta-geral-academic-year">Ano Lectivo</label>
                        <div
                            wire:key="pauta-geral-academic-year-{{ $this->institution_id ?: 'none' }}-{{ count($academicYearOptions) }}"
                            id="pauta-geral-academic-year"
                            class="pauta-geral-selectbox"
                            x-data="pautaGeralSearchSelect({ options: @js($academicYearOptions), value: @entangle('academic_year_id').live, placeholder: '{{ $this->institution_id ? 'Seleccione o ano lectivo...' : 'Seleccione a instituição primeiro' }}', searchPlaceholder: 'Pesquisar ano lectivo...', disabled: {{ $this->institution_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="pauta-geral-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="pauta-geral-select-value" x-bind:class="{ 'pauta-geral-select-placeholder': ! selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="pauta-geral-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="pauta-geral-select-clear" x-show="value !== null && value !== '' && ! disabled" x-on:click.stop="clear()" aria-label="Limpar Ano Lectivo"></button>
                            <div class="pauta-geral-select-panel" x-cloak x-show="open" x-transition>
                                <input x-ref="searchInput" class="pauta-geral-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="pauta-geral-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="pauta-geral-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="pauta-geral-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pauta-geral-field">
                        <label for="pauta-geral-course">Curso</label>
                        <div
                            wire:key="pauta-geral-course-{{ $this->institution_id ?: 'none' }}-{{ $this->academic_year_id ?: 'none' }}-{{ count($courseOptions) }}"
                            id="pauta-geral-course"
                            class="pauta-geral-selectbox"
                            x-data="pautaGeralSearchSelect({ options: @js($courseOptions), value: @entangle('course_id').live, placeholder: '{{ $this->academic_year_id ? 'Seleccione o curso...' : 'Seleccione o ano primeiro' }}', searchPlaceholder: 'Pesquisar curso...', disabled: {{ $this->academic_year_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="pauta-geral-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="pauta-geral-select-value" x-bind:class="{ 'pauta-geral-select-placeholder': ! selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="pauta-geral-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="pauta-geral-select-clear" x-show="value !== null && value !== '' && ! disabled" x-on:click.stop="clear()" aria-label="Limpar Curso"></button>
                            <div class="pauta-geral-select-panel" x-cloak x-show="open" x-transition>
                                <input x-ref="searchInput" class="pauta-geral-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="pauta-geral-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="pauta-geral-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="pauta-geral-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pauta-geral-field">
                        <label for="pauta-geral-class">Turma</label>
                        <div
                            wire:key="pauta-geral-class-{{ $this->institution_id ?: 'none' }}-{{ $this->academic_year_id ?: 'none' }}-{{ $this->course_id ?: 'none' }}-{{ count($classOptions) }}"
                            id="pauta-geral-class"
                            class="pauta-geral-selectbox"
                            x-data="pautaGeralSearchSelect({ options: @js($classOptions), value: @entangle('class_id').live, placeholder: '{{ $this->course_id ? 'Seleccione a turma...' : 'Seleccione o curso primeiro' }}', searchPlaceholder: 'Pesquisar turma...', disabled: {{ $this->course_id ? 'false' : 'true' }} })"
                            x-on:click.outside="close()"
                        >
                            <button type="button" class="pauta-geral-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                <span class="pauta-geral-select-value" x-bind:class="{ 'pauta-geral-select-placeholder': ! selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                <svg class="pauta-geral-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <button type="button" class="pauta-geral-select-clear" x-show="value !== null && value !== '' && ! disabled" x-on:click.stop="clear()" aria-label="Limpar Turma"></button>
                            <div class="pauta-geral-select-panel" x-cloak x-show="open" x-transition>
                                <input x-ref="searchInput" class="pauta-geral-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                <div class="pauta-geral-select-options">
                                    <template x-for="option in filteredOptions" x-bind:key="option.value">
                                        <button type="button" class="pauta-geral-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                    </template>
                                    <div class="pauta-geral-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pauta-geral-actions">
                    <x-filament::button
                        wire:click="pesquisar"
                        icon="heroicon-o-magnifying-glass"
                        :disabled="!$this->institution_id || !$this->academic_year_id || !$this->course_id || !$this->class_id"
                    >
                        Pesquisar
                    </x-filament::button>

                    @if($this->showTable && $this->class_id)
                        {{ $this->printPautaGeralAction }}
                    @endif
                </div>
            </div>
        </div>

        @if($this->showTable)
            <div class="pauta-geral-legend-card">
                <h4 class="pauta-geral-legend-title">Legenda das Disciplinas:</h4>
                <div class="pauta-geral-legend-items">
                    @foreach(\App\Models\Subject::orderBy('name')->get() as $subject)
                        @php
                            $words = explode(' ', $subject->name);
                            $abbr = '';

                            if (count($words) > 1) {
                                foreach ($words as $word) {
                                    if (strlen($word) > 2) {
                                        $abbr .= strtoupper(substr($word, 0, 1));
                                    }
                                }
                            }

                            if (strlen($abbr) < 2) {
                                $abbr = substr($subject->name, 0, 5);
                            }
                        @endphp
                        <span><strong>{{ $abbr }}</strong> = {{ $subject->name }}</span>
                    @endforeach
                    <span><strong>MG</strong> = Média Geral</span>
                </div>
            </div>

            <div>
                {{ $this->table }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
