@php
    $typeLabels = [
        'frequencia' => 'Frequência',
        'exame' => 'Exame',
        'pratico' => 'Prático',
        'comportamental' => 'Comportamental',
    ];

    $typeClasses = [
        'frequencia' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400',
        'exame' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400',
        'pratico' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400',
        'comportamental' => 'bg-yellow-50 text-yellow-700 ring-yellow-700/20 dark:bg-yellow-400/10 dark:text-yellow-400',
    ];
@endphp

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Disciplina</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Tipo</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Nota</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Formador</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($evaluations as $evaluation)
                    @php
                        $type = (string) $evaluation->evaluation_type;
                        $score = is_numeric($evaluation->score) ? (float) $evaluation->score : null;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                            {{ $evaluation->subject?->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $typeClasses[$type] ?? 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400' }}">
                                {{ $typeLabels[$type] ?? ucfirst($type ?: 'N/A') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $score !== null && $score >= 10 ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400' }}">
                                {{ $score !== null ? number_format($score, 1, ',', '.') : 'N/A' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $evaluation->evaluator_name ?: 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $evaluation->evaluated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Nenhuma avaliação encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
        Total: {{ $evaluations->count() }} avaliação(ões)
    </div>
</div>
