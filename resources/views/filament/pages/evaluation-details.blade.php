@php
    $evaluations = collect($evaluations ?? []);

    $typeLabels = [
        'frequencia' => 'Frequência',
        'exame' => 'Exame',
        'pratico' => 'Prático',
        'comportamental' => 'Comportamental',
    ];

    $typeClasses = [
        'frequencia' => 'is-frequency',
        'exame' => 'is-exam',
        'pratico' => 'is-practical',
        'comportamental' => 'is-behavior',
    ];
@endphp

<style>
    .sigef-evaluation-history {
        display: flex;
        flex-direction: column;
        gap: 0;
        padding-top: 2px;
    }

    .sigef-evaluation-table-wrap {
        background: #fff;
        border: 1px solid #dbe3f0;
        border-radius: 8px;
        max-height: min(430px, 55vh);
        overflow: auto;
    }

    .sigef-evaluation-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 860px;
        width: 100%;
    }

    .sigef-evaluation-table th {
        background: #061b49;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        padding: 12px 16px;
        position: sticky;
        text-align: left;
        top: 0;
        white-space: nowrap;
        z-index: 1;
    }

    .sigef-evaluation-table th.is-center,
    .sigef-evaluation-table td.is-center {
        text-align: center;
    }

    .sigef-evaluation-table td {
        border-top: 1px solid #e5eaf2;
        color: #111827;
        font-size: 13px;
        line-height: 1.45;
        padding: 13px 16px;
        vertical-align: middle;
    }

    .sigef-evaluation-table tbody tr:nth-child(even) td {
        background: #f9fbfe;
    }

    .sigef-evaluation-table tbody tr:hover td {
        background: #eef5ff;
    }

    .sigef-evaluation-subject {
        color: #061b49;
        display: block;
        font-weight: 800;
        max-width: 260px;
    }

    .sigef-evaluation-muted {
        color: #64748b;
        font-size: 12px;
    }

    .sigef-evaluation-pill {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .sigef-evaluation-pill.is-frequency {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .sigef-evaluation-pill.is-exam {
        background: #e0e7ff;
        color: #3730a3;
    }

    .sigef-evaluation-pill.is-practical {
        background: #dcfce7;
        color: #15803d;
    }

    .sigef-evaluation-pill.is-behavior {
        background: #fef3c7;
        color: #92400e;
    }

    .sigef-evaluation-pill.is-neutral {
        background: #f1f5f9;
        color: #334155;
    }

    .sigef-evaluation-score {
        border-radius: 8px;
        display: inline-flex;
        font-size: 13px;
        font-weight: 900;
        justify-content: center;
        min-width: 54px;
        padding: 8px 10px;
    }

    .sigef-evaluation-score.is-pass {
        background: #dcfce7;
        color: #166534;
    }

    .sigef-evaluation-score.is-fail {
        background: #fee2e2;
        color: #991b1b;
    }

    .sigef-evaluation-empty {
        color: #64748b;
        padding: 24px;
        text-align: center;
    }

    .dark .sigef-evaluation-table-wrap {
        background: rgba(15, 23, 42, .72);
        border-color: rgba(255, 255, 255, .12);
    }

    .dark .sigef-evaluation-muted,
    .dark .sigef-evaluation-empty {
        color: #94a3b8;
    }

    .dark .sigef-evaluation-subject,
    .dark .sigef-evaluation-table td {
        color: #e5e7eb;
    }

    .dark .sigef-evaluation-table td {
        border-top-color: rgba(255, 255, 255, .1);
    }

    .dark .sigef-evaluation-table tbody tr:nth-child(even) td {
        background: rgba(255, 255, 255, .03);
    }

    .dark .sigef-evaluation-table tbody tr:hover td {
        background: rgba(59, 130, 246, .12);
    }
</style>

<div class="sigef-evaluation-history">
    <div class="sigef-evaluation-table-wrap">
        <table class="sigef-evaluation-table">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Tipo</th>
                    <th class="is-center">Nota</th>
                    <th>Formador</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($evaluations as $evaluation)
                    @php
                        $type = (string) $evaluation->evaluation_type;
                        $score = is_numeric($evaluation->score) ? (float) $evaluation->score : null;
                        $typeClass = $typeClasses[$type] ?? 'is-neutral';
                    @endphp

                    <tr>
                        <td>
                            <span class="sigef-evaluation-subject">{{ $evaluation->subject?->name ?? 'N/A' }}</span>
                            <span class="sigef-evaluation-muted">{{ $evaluation->phase?->name ?? 'Sem fase associada' }}</span>
                        </td>
                        <td>
                            <span class="sigef-evaluation-pill {{ $typeClass }}">
                                {{ $typeLabels[$type] ?? ucfirst($type ?: 'N/A') }}
                            </span>
                        </td>
                        <td class="is-center">
                            <span class="sigef-evaluation-score {{ $score !== null && $score >= 10 ? 'is-pass' : 'is-fail' }}">
                                {{ $score !== null ? number_format($score, 1, ',', '.') : '-' }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $evaluation->evaluator_name ?: 'N/A' }}</strong>
                        </td>
                        <td>
                            {{ $evaluation->evaluated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="sigef-evaluation-empty">
                            Nenhuma avaliação encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
