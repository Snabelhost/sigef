@php
    $ocorrencias = \App\Models\StudentLeave::where('student_id', $studentId)
        ->orderBy('created_at', 'desc')
        ->get();
    
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
    
    $statusConfig = [
        'pending' => ['label' => 'Pendente', 'color' => '#d97706'],
        'approved' => ['label' => 'Aprovada', 'color' => '#059669'],
        'rejected' => ['label' => 'Rejeitada', 'color' => '#dc2626'],
    ];
@endphp

<div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; width: 100%;">
    {{-- Header --}}
    <div style="background: #041B4E; color: white; display: flex; padding: 10px 0;">
        <div style="width: 20%; padding: 0 12px; font-weight: 600; font-size: 13px;">Tipo</div>
        <div style="width: 12%; padding: 0 12px; font-weight: 600; font-size: 13px; text-align: center;">Início</div>
        <div style="width: 12%; padding: 0 12px; font-weight: 600; font-size: 13px; text-align: center;">Fim</div>
        <div style="width: 26%; padding: 0 12px; font-weight: 600; font-size: 13px;">Motivo</div>
        <div style="width: 12%; padding: 0 12px; font-weight: 600; font-size: 13px; text-align: center;">Estado</div>
        <div style="width: 18%; padding: 0 12px; font-weight: 600; font-size: 13px; text-align: center;">Data</div>
    </div>
    
    {{-- Body com scroll --}}
    <div style="max-height: 150px; overflow-y: auto; background: white;">
        @forelse($ocorrencias as $ocorrencia)
            @php
                $status = $statusConfig[$ocorrencia->status] ?? ['label' => $ocorrencia->status, 'color' => '#6b7280'];
            @endphp
            <div style="display: flex; border-bottom: 1px solid #f3f4f6; padding: 10px 0; align-items: center;">
                <div style="width: 20%; padding: 0 12px;">
                    <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                        {{ $typeLabels[$ocorrencia->leave_type] ?? $ocorrencia->leave_type }}
                    </span>
                </div>
                <div style="width: 12%; padding: 0 12px; font-size: 13px; color: #374151; text-align: center;">
                    {{ $ocorrencia->start_date?->format('d/m/Y') }}
                </div>
                <div style="width: 12%; padding: 0 12px; font-size: 13px; color: #374151; text-align: center;">
                    {{ $ocorrencia->end_date?->format('d/m/Y') }}
                </div>
                <div style="width: 26%; padding: 0 12px; font-size: 13px; color: #374151;">
                    {{ \Illuminate\Support\Str::limit($ocorrencia->reason ?? '-', 25) }}
                </div>
                <div style="width: 12%; padding: 0 12px; text-align: center;">
                    <span style="color: {{ $status['color'] }}; font-size: 12px; font-weight: 600;">
                        {{ $status['label'] }}
                    </span>
                </div>
                <div style="width: 18%; padding: 0 12px; font-size: 12px; color: #374151; text-align: center;">
                    {{ $ocorrencia->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>
        @empty
            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                Nenhuma ocorrência registada.
            </div>
        @endforelse
    </div>
    
    {{-- Footer --}}
    <div style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 12px; color: #6b7280;">Total: {{ $ocorrencias->count() }} ocorrência(s)</span>
        @if($ocorrencias->count() > 3)
            <span style="font-size: 11px; color: #9ca3af;">↕ Deslize para ver mais</span>
        @endif
    </div>
</div>
