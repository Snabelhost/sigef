<div class="space-y-4">
    @if($assignments->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Turma</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Disciplina</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">Activo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($assignments as $assignment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $assignment->studentClass->name ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $assignment->subject->name ?? '-' }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($assignment->is_active)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-300">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-4">
            Total de disciplinas: <strong class="text-primary-600">{{ $assignments->count() }}</strong>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-academic-cap class="w-12 h-12 mx-auto mb-2" />
            <p>Nenhuma disciplina atribuída</p>
        </div>
    @endif
</div>
