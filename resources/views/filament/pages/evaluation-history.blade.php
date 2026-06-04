<div class="overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Disciplina</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Tipo</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Nota</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Avaliador</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Data</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluations as $evaluation)
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $evaluation->subject?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @switch($evaluation->evaluation_type)
                                @case('frequencia') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                                @case('exame') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @break
                                @case('pratico') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                                @case('comportamental') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                                @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                            @endswitch
                        ">
                            {{ ucfirst($evaluation->evaluation_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                            {{ $evaluation->score >= 10 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}
                        ">
                            {{ number_format($evaluation->score, 1) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $evaluation->evaluator_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $evaluation->evaluated_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        Nenhuma avaliação encontrada
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
