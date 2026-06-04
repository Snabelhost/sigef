<div class="space-y-6">
    {{-- Header com Avatar e Info Principal --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-content p-6">
            <div class="flex items-start gap-6">
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    <div class="fi-avatar fi-circular w-20 h-20 overflow-hidden rounded-full bg-primary-500 flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">
                            {{ strtoupper(substr($student->candidate->full_name ?? 'A', 0, 2)) }}
                        </span>
                    </div>
                </div>
                
                {{-- Info Principal --}}
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white truncate">
                        {{ $student->candidate->full_name ?? 'N/A' }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Nº Aluno: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $student->student_number }}</span>
                    </p>
                    
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        {{-- Badge Tipo --}}
                        <span class="fi-badge fi-color-custom fi-badge-size-md inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                            @php
                                $typeColor = \App\Models\StudentType::where('name', $student->student_type)->value('color') ?? 'primary';
                            @endphp
                            @if($typeColor === 'success') bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30
                            @elseif($typeColor === 'warning') bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30
                            @elseif($typeColor === 'danger') bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30
                            @elseif($typeColor === 'info') bg-info-50 text-info-600 ring-info-600/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30
                            @else bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30
                            @endif">
                            <x-heroicon-m-academic-cap class="w-4 h-4"/>
                            {{ $student->student_type ?? 'N/A' }}
                        </span>
                        
                        {{-- Badge Instituição --}}
                        @if($student->institution)
                        <span class="fi-badge fi-color-gray fi-badge-size-md inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30">
                            <x-heroicon-m-building-library class="w-4 h-4"/>
                            {{ $student->institution->name }}
                        </span>
                        @endif
                        
                        {{-- Badge Fase Actual --}}
                        @if($student->currentPhase)
                        <span class="fi-badge fi-color-gray fi-badge-size-md inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30">
                            <x-heroicon-m-flag class="w-4 h-4"/>
                            {{ $student->currentPhase->name }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estatísticas Rápidas --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 p-3 rounded-lg bg-primary-50 dark:bg-primary-500/10">
                    <x-heroicon-o-user-group class="w-6 h-6 text-primary-600 dark:text-primary-400"/>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $enrollments->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Turmas Inscritas</p>
                </div>
            </div>
        </div>
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 p-3 rounded-lg bg-success-50 dark:bg-success-500/10">
                    <x-heroicon-o-book-open class="w-6 h-6 text-success-600 dark:text-success-400"/>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $subjects->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Disciplinas Inscritas</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Inscrições em Turmas --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center border-b border-gray-200 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-500/10">
                    <x-heroicon-o-user-group class="w-5 h-5 text-primary-600 dark:text-primary-400"/>
                </div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Inscrições em Turmas</h3>
            </div>
        </div>
        
        <div class="fi-section-content">
            @if($enrollments->count() > 0)
                <div class="fi-ta-table-container overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Turma</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Fase</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Sala</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($enrollments as $enrollment)
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-academic-cap class="w-4 h-4 text-gray-400"/>
                                            {{ $enrollment->studentClass->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $enrollment->coursePhase->name ?? '-' }}</td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $enrollment->classroom ?? '-' }}</td>
                                    <td class="fi-ta-cell px-4 py-3 text-center">
                                        @if($enrollment->is_active)
                                            <span class="fi-badge fi-color-success inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                <x-heroicon-m-check-circle class="w-3.5 h-3.5"/>
                                                Activo
                                            </span>
                                        @else
                                            <span class="fi-badge fi-color-danger inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                                <x-heroicon-m-x-circle class="w-3.5 h-3.5"/>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 px-6">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                        <x-heroicon-o-user-group class="w-8 h-8 text-gray-400"/>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma inscrição em turmas</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Disciplinas Inscritas --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center border-b border-gray-200 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-success-50 dark:bg-success-500/10">
                    <x-heroicon-o-book-open class="w-5 h-5 text-success-600 dark:text-success-400"/>
                </div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Disciplinas Inscritas</h3>
            </div>
        </div>
        
        <div class="fi-section-content">
            @if($subjects->count() > 0)
                <div class="fi-ta-table-container overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Disciplina</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Turma</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white">Fase</th>
                                <th class="fi-ta-header-cell px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($subjects as $subject)
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-book-open class="w-4 h-4 text-gray-400"/>
                                            {{ $subject->subject->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $subject->studentClass->name ?? '-' }}</td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $subject->coursePhase->name ?? '-' }}</td>
                                    <td class="fi-ta-cell px-4 py-3 text-center">
                                        @if($subject->is_active)
                                            <span class="fi-badge fi-color-success inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                <x-heroicon-m-check-circle class="w-3.5 h-3.5"/>
                                                Activo
                                            </span>
                                        @else
                                            <span class="fi-badge fi-color-danger inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                                <x-heroicon-m-x-circle class="w-3.5 h-3.5"/>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 px-6">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                        <x-heroicon-o-book-open class="w-8 h-8 text-gray-400"/>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma disciplina inscrita</p>
                </div>
            @endif
        </div>
    </div>
</div>
