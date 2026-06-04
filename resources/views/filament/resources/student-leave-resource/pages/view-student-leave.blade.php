<x-filament-panels::page>
    @php
        $typeLabels = [
            'dispensa_saude' => 'Dispensa - Saúde',
            'dispensa_pessoal' => 'Dispensa - Pessoal',
            'dispensa_servico' => 'Dispensa - Serviço',
            'dispensa_falecimento' => 'Dispensa - Falecimento',
            'dispensa_outro' => 'Dispensa - Outro',
            'falta_justificada' => 'Falta Justificada',
            'falta_injustificada' => 'Falta Injustificada',
            'reprovado_faltas' => 'Reprovado Faltas',
            'reprovado_desistencia' => 'Reprovado Desistência',
        ];
        
        $typeColors = [
            'dispensa_saude' => 'bg-blue-100 text-blue-800',
            'dispensa_pessoal' => 'bg-blue-100 text-blue-800',
            'dispensa_servico' => 'bg-blue-100 text-blue-800',
            'dispensa_falecimento' => 'bg-blue-100 text-blue-800',
            'dispensa_outro' => 'bg-blue-100 text-blue-800',
            'falta_justificada' => 'bg-yellow-100 text-yellow-800',
            'falta_injustificada' => 'bg-red-100 text-red-800',
            'reprovado_faltas' => 'bg-red-100 text-red-800',
            'reprovado_desistencia' => 'bg-red-100 text-red-800',
        ];
        
        $statusLabels = [
            'pending' => 'Pendente',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
        ];
        
        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];
    @endphp

    {{-- Informações do Formando --}}
    <x-filament::section>
        <x-slot name="heading">
            Informações do Formando
        </x-slot>
        
        <div class="grid grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nº de Ordem</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->record->student?->student_number ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nome Completo</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->record->student?->candidate?->full_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Cia</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->record->student?->cia ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pelotão</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->record->student?->platoon ?? 'N/A' }}</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Resumo de Ocorrências --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Resumo de Ocorrências
        </x-slot>
        
        <div class="grid grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Dispensas</p>
                <span class="inline-flex items-center justify-center px-3 py-1 text-lg font-bold rounded-full bg-blue-100 text-blue-800">
                    {{ $this->getTotalDispensas() }}
                </span>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Faltas Justificadas</p>
                <span class="inline-flex items-center justify-center px-3 py-1 text-lg font-bold rounded-full bg-yellow-100 text-yellow-800">
                    {{ $this->getTotalFaltasJustificadas() }}
                </span>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Faltas Injustificadas</p>
                <span class="inline-flex items-center justify-center px-3 py-1 text-lg font-bold rounded-full bg-red-100 text-red-800">
                    {{ $this->getTotalFaltasInjustificadas() }}
                </span>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Geral</p>
                <span class="inline-flex items-center justify-center px-3 py-1 text-lg font-bold rounded-full bg-primary-100 text-primary-800">
                    {{ $this->getTotalOcorrencias() }}
                </span>
            </div>
        </div>
    </x-filament::section>

    {{-- Histórico de Ocorrências --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Histórico de Ocorrências
        </x-slot>
        
        <div class="w-full overflow-x-auto">
            <table class="w-full text-sm" style="table-layout: fixed;">
                <thead>
                    <tr class="bg-primary-600 text-white">
                        <th style="width: 20%; padding: 12px 16px;" class="text-left font-medium">Tipo</th>
                        <th style="width: 12%; padding: 12px 16px;" class="text-center font-medium">Início</th>
                        <th style="width: 12%; padding: 12px 16px;" class="text-center font-medium">Fim</th>
                        <th style="width: 30%; padding: 12px 16px;" class="text-left font-medium">Motivo</th>
                        <th style="width: 12%; padding: 12px 16px;" class="text-center font-medium">Estado</th>
                        <th style="width: 14%; padding: 12px 16px;" class="text-center font-medium">Data Registo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->getOcorrencias() as $ocorrencia)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td style="padding: 12px 16px;">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$ocorrencia->leave_type] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $typeLabels[$ocorrencia->leave_type] ?? $ocorrencia->leave_type }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px;" class="text-center text-gray-600 dark:text-gray-400">
                                {{ $ocorrencia->start_date?->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px 16px;" class="text-center text-gray-600 dark:text-gray-400">
                                {{ $ocorrencia->end_date?->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px 16px;" class="text-gray-600 dark:text-gray-400">
                                {{ $ocorrencia->reason ?? '-' }}
                            </td>
                            <td style="padding: 12px 16px;" class="text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ocorrencia->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$ocorrencia->status] ?? $ocorrencia->status }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px;" class="text-center text-gray-600 dark:text-gray-400">
                                {{ $ocorrencia->created_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 24px;" class="text-center text-gray-500">
                                Nenhuma ocorrência registada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
