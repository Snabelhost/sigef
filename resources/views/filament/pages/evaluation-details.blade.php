<div class="space-y-8">
    {{-- Informações do Formando --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Informações do Formando
            </h3>
        </div>
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <div style="display: flex; flex-direction: row; gap: 40px; flex-wrap: wrap;">
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #111827;">Nº de Ordem</span>
                        <span style="color: #6b7280;">{{ $record->student?->student_number ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #111827;">Nome Completo</span>
                        <span style="color: #6b7280;">{{ $record->student?->candidate?->full_name ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #111827;">Cia</span>
                        <span style="color: #6b7280;">{{ $record->student?->cia ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #111827;">Pelotão</span>
                        <span style="color: #6b7280;">{{ $record->student?->platoon ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Histórico de Avaliações --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-top: 24px;">
        <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Histórico de Avaliações
            </h3>
        </div>
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/5" style="width: 100%; table-layout: fixed;">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white" style="width: 30%;">Disciplina</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white" style="width: 15%;">Tipo</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white" style="width: 10%;">Nota</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white" style="width: 25%;">Avaliador</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-950 dark:text-white" style="width: 20%;">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($evaluations as $evaluation)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $evaluation->subject?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                        @switch($evaluation->evaluation_type)
                                            @case('frequencia') bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 @break
                                            @case('exame') bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 @break
                                            @case('pratico') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 @break
                                            @case('comportamental') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 @break
                                            @default bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400
                                        @endswitch
                                    ">
                                        {{ ucfirst($evaluation->evaluation_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-center">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                        {{ $evaluation->score >= 10 
                                            ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400' 
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400' 
                                        }}
                                    ">
                                        {{ number_format($evaluation->score, 1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $evaluation->evaluator_name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $evaluation->evaluated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma avaliação encontrada
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                Total: {{ $evaluations->count() }} avaliação(ões)
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
