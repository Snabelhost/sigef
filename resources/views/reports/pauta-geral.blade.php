@extends('reports.layout')
@section('title', 'PAUTA GERAL DE CLASSIFICACAO')
@section('subtitle', 'Mapa geral de avaliacoes por disciplina')
@section('filters')
@if(isset($institution)) <strong>Escola:</strong> {{ $institution->name }} @endif
@if($class) <strong>Turma:</strong> {{ $class->name }} @endif
@endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            @foreach($subjects as $s) <th class="text-center" style="font-size:8px;">{{ $s->acronym ?? \Illuminate\Support\Str::limit($s->name, 6) }}</th> @endforeach
            <th class="text-center">MG</th>
            <th class="text-center">Res.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        @php
        $institutionId = $institution->id ?? null;
        // Filter evaluations by institution if set
        $evals = $institutionId
            ? $r->evaluations->where('institution_id', $institutionId)
            : $r->evaluations;

        $grades = [];
        $subjectAverages = [];
        $allCompleted = true;

        foreach ($subjects as $s) {
            $subEvals = $evals->where('subject_id', $s->id);

            if ($subEvals->isEmpty()) {
                $grades[] = null;
                $allCompleted = false;
                continue;
            }

            // Calculate using proper formula: NPP*40% + Exame*60%
            $nppScores = $subEvals->where('evaluation_type', 'npp')
                ->pluck('score')
                ->filter(fn($v) => $v !== null && $v !== '');
            $nppAvg = $nppScores->isNotEmpty() ? $nppScores->avg() : 0;
            $exameScore = $subEvals->where('evaluation_type', 'exame')->first()?->score;

            if ($nppScores->isEmpty() || !$exameScore) {
                // Incomplete - show partial if any score available
                $partialGrade = $nppScores->isNotEmpty() ? $nppAvg : ($exameScore ? floatval($exameScore) : null);
                $grades[] = $partialGrade;
                $allCompleted = false;
            } else {
                $subjAvg = ($nppAvg * 0.4) + (floatval($exameScore) * 0.6);
                $grades[] = $subjAvg;
                $subjectAverages[] = $subjAvg;
            }
        }

        $generalAvg = count($subjectAverages) ? array_sum($subjectAverages) / count($subjectAverages) : null;

        // Determine result
        if (empty($subjectAverages) || $generalAvg === null) {
            $resultado = 'Pn';
            $badgeClass = 'badge-warning';
        } elseif (!$allCompleted) {
            $resultado = 'Pn';
            $badgeClass = 'badge-warning';
        } elseif ($generalAvg >= 10) {
            $resultado = 'Ap';
            $badgeClass = 'badge-success';
        } else {
            $resultado = 'Rp';
            $badgeClass = 'badge-danger';
        }
        @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->candidate?->full_name ?? $r->full_name ?? '-' }}</td>
            @foreach($grades as $g) <td class="text-center">{{ $g !== null ? number_format($g, 0) : '-' }}</td> @endforeach
            <td class="text-center text-bold">{{ $generalAvg !== null ? number_format($generalAvg, 1) : '-' }}</td>
            <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $resultado }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection
