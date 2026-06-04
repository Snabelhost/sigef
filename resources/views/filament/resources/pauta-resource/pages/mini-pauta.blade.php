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
                        <img src="{{ asset('images/logo-pna.png') }}" alt="Logo PNA" style="width: 120px; height: auto;">
                    </td>
                    <td class="align-top">
                        <h2 style="font-size: 18px; font-weight: bold; color: #041B4E; margin-bottom: 20px;">
                            MINI PAUTA DO PROFESSOR : {{ $this->getTrainerName() }}
                        </h2>

                        <table style="font-size: 14px; width: 100%;">
                            {{-- Linha 1: Curso e Turma --}}
                            <tr>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 0; width: 80px;"><strong>Curso:</strong></td>
                                <td style="padding: 8px 15px 8px 0;">
                                    <select wire:model.live="course_id" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" style="min-width: 280px; padding: 8px 12px; font-size: 14px;">
                                        <option value="">Seleccione o curso...</option>
                                        @php
                                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                        if ($tenantId) {
                                        $courseIds = \App\Models\CourseMap::where('institution_id', $tenantId)->pluck('course_id')->unique();
                                        $courses = \App\Models\Course::whereIn('id', $courseIds)
                                        ->orWhere('institution_id', $tenantId)
                                        ->orderBy('name')->get()->unique('id');
                                        } else {
                                        $courses = \App\Models\Course::orderBy('name')->get();
                                        }
                                        @endphp
                                        @foreach($courses as $course)
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
                            {{-- Linha 2: Disciplina e Instituição --}}
                            <tr>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 0;"><strong>Disciplina:</strong></td>
                                <td style="padding: 8px 15px 8px 0;">
                                    <select wire:model.live="subject_id" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" style="min-width: 280px; padding: 8px 12px; font-size: 14px;" @if(!$this->class_id) disabled @endif>
                                        <option value="">Seleccione a disciplina...</option>
                                        @foreach($this->getSubjects() as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 20px;"><strong>Instituição:</strong></td>
                                <td style="padding: 8px 0; color: #374151;">
                                    @if($this->class_id && $this->getSelectedClass())
                                    {{ $this->getSelectedClass()->institution->name ?? '-' }}
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                            {{-- Linha 3: Ano --}}
                            @if($this->class_id && $this->getSelectedClass())
                            @php $selectedClass = $this->getSelectedClass(); @endphp
                            <tr>
                                <td style="font-weight: bold; color: #1f2937; padding: 8px 15px 8px 0;"><strong>Ano:</strong></td>
                                <td style="padding: 8px 0; color: #374151;" colspan="3">{{ $selectedClass->academicYear->year ?? '-' }}</td>
                            </tr>
                            @endif
                        </table>

                        {{-- Botão Pesquisar --}}
                        <div style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
                            <x-filament::button
                                wire:click="pesquisar"
                                icon="heroicon-o-magnifying-glass"
                                :disabled="!$this->class_id || !$this->subject_id">
                                Pesquisar
                            </x-filament::button>

                            @if($this->showTable && $this->class_id && $this->subject_id)
                            <a href="{{ route('pauta.mini-pauta.print', ['turma' => $this->class_id, 'disciplina' => $this->subject_id]) }}" target="_blank" style="text-decoration: none;">
                                <x-filament::button
                                    tag="span"
                                    icon="heroicon-o-printer">
                                    Imprimir Pauta
                                </x-filament::button>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Tabela de Notas --}}
        @if($this->showTable)
        <div style="margin-top: 25px;">
            {{ $this->table }}
        </div>
        @endif
    </div>
</x-filament-panels::page>