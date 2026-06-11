<x-filament-panels::page>
    <style>
        .sigef-full-header {
            display: none !important;
        }
    </style>
    <div class="space-y-8">
        {{-- Cabeçalho --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding: 30px 35px;">
            <table class="w-full">
                <tr>
                    <td class="align-top" style="width: 140px; padding-right: 30px;">
                        <img src="{{ $this->getInstitutionLogoUrl() }}" alt="Logo da instituição" style="width: 120px; height: 120px; object-fit: contain;">
                    </td>
                    <td class="align-top">
                        <h2 style="font-size: 18px; font-weight: bold; color: #041B4E; margin-bottom: 20px;">
                            PAUTA GERAL DE AVALIAÇÕES
                        </h2>

                        <table style="font-size: 14px; width: 100%;">
                            {{-- Linha 1: Curso e Turma --}}
                            <tr>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 0; width: 80px;"><strong>Curso:</strong></td>
                                <td style="padding: 8px 15px 8px 0;">
                                    <select wire:model.live="course_id" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" style="min-width: 280px; padding: 8px 12px; font-size: 14px;">
                                        <option value="">Seleccione o curso...</option>
                                        @foreach(\App\Models\Course::all() as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 20px; width: 80px;"><strong>Turma:</strong></td>
                                <td style="padding: 8px 0;">
                                    <select wire:model.live="class_id" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" style="min-width: 280px; padding: 8px 12px; font-size: 14px;" @if(!$this->course_id) disabled @endif>
                                        <option value="">Seleccione a turma...</option>
                                        @foreach($this->getClasses() as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            {{-- Linha 2: Instituição e Ano --}}
                            @if($this->class_id && $this->getSelectedClass())
                            @php $selectedClass = $this->getSelectedClass(); @endphp
                            <tr>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 0;"><strong>Instituição:</strong></td>
                                <td style="padding: 8px 15px 8px 0; color: #374151;">{{ $selectedClass->institution->name ?? '-' }}</td>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 20px;"><strong>Ano:</strong></td>
                                <td style="padding: 8px 0; color: #374151;">{{ $selectedClass->academicYear->year ?? '-' }}</td>
                            </tr>
                            @endif
                        </table>

                        {{-- Botão Pesquisar --}}
                        <div style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
                            <x-filament::button
                                wire:click="pesquisar"
                                icon="heroicon-o-magnifying-glass"
                                :disabled="!$this->class_id">
                                Pesquisar
                            </x-filament::button>

                            @if($this->showTable && $this->class_id)
                            <a href="{{ route('pauta.pauta-geral.print', ['turma' => $this->class_id]) }}" target="_blank" style="text-decoration: none;">
                                <x-filament::button
                                    tag="span"
                                    icon="heroicon-o-printer">
                                    Imprimir Pauta Geral
                                </x-filament::button>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Legenda das Disciplinas --}}
        @if($this->showTable)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding: 15px 20px;">
            <h4 style="font-size: 14px; font-weight: bold; color: #041B4E; margin-bottom: 10px;">Legenda das Disciplinas:</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 12px;">
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
                    $abbr=substr($subject->name, 0, 5);
                    }
                    @endphp
                    <span style="color: #374151;"><strong>{{ $abbr }}</strong> = {{ $subject->name }}</span>
                    @endforeach
                    <span style="color: #374151;"><strong>MG</strong> = Média Geral</span>
            </div>
        </div>
        @endif

        {{-- Tabela de Notas --}}
        @if($this->showTable)
        <div style="margin-top: 25px; overflow-x: auto;">
            {{ $this->table }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
