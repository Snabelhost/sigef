@extends('reports.layout')
@section('title', 'Mini Pauta')
@section('filters')
@if(isset($institution)) <strong>Escola:</strong> {{ $institution->name }} @endif
@if($class) <strong>Turma:</strong> {{ $class->name }} @endif
@endsection
@section('summary') <span>Total de Formandos: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Média Geral</th>
            <th>Resultado</th>
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

        // Calculate subject averages using the proper formula (NPP*40% + Exame*60%)
        $subjectIds = $evals->pluck('subject_id')->unique();
        $subjectAverages = [];
        $allCompleted = true;

        foreach ($subjectIds as $subjectId) {
        $subEvals = $evals->where('subject_id', $subjectId);
        $nppScores = $subEvals->where('evaluation_type', 'npp')
        ->pluck('score')
        ->filter(fn($s) => $s !== null && $s !== '');
        $nppAvg = $nppScores->isNotEmpty() ? $nppScores->avg() : 0;
        $exameScore = $subEvals->where('evaluation_type', 'exame')->first()?->score;

        if ($nppScores->isEmpty() || !$exameScore) {
        $allCompleted = false;
        continue;
        }

        $subjectAverages[] = ($nppAvg * 0.4) + (floatval($exameScore) * 0.6);
        }

        $generalAvg = count($subjectAverages) ? array_sum($subjectAverages) / count($subjectAverages) : null;

        // Determine result
        if ($subjectIds->isEmpty() || $generalAvg === null) {
        $resultado = 'Pendente';
        $badgeClass = 'badge-warning';
        } elseif (!$allCompleted) {
        $resultado = 'Pendente';
        $badgeClass = 'badge-warning';
        } elseif ($generalAvg >= 10) {
        $resultado = 'Aprovado';
        $badgeClass = 'badge-success';
        } else {
        $resultado = 'Reprovado';
        $badgeClass = 'badge-danger';
        }
        @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->candidate?->full_name ?? '-' }}</td>
            <td class="text-bold">{{ $generalAvg !== null ? number_format($generalAvg, 1) : '-' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $resultado }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection