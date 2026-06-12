@extends('reports.layout')

@section('title', 'CERTIFICADOS')
@section('subtitle', 'Situacao academica para emissao de certificados')

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
    @if(! $institution && ! $class) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">N</th>
                    <th class="text-left" style="width: 33%;">Nome</th>
                    <th style="width: 12%;">NIP/NURI</th>
                    <th class="text-left" style="width: 28%;">Curso</th>
                    <th style="width: 10%;">Media</th>
                    <th style="width: 12%;">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    @php
                        $institutionId = $institution->id ?? null;
                        $evaluations = $institutionId
                            ? $record->evaluations->where('institution_id', $institutionId)
                            : $record->evaluations;
                        $subjectIds = $evaluations->pluck('subject_id')->unique();
                        $subjectAverages = [];
                        $allCompleted = true;

                        foreach ($subjectIds as $subjectId) {
                            $subjectEvaluations = $evaluations->where('subject_id', $subjectId);
                            $nppScores = $subjectEvaluations
                                ->where('evaluation_type', 'npp')
                                ->pluck('score')
                                ->filter(fn ($score) => $score !== null && $score !== '');
                            $nppAverage = $nppScores->isNotEmpty() ? $nppScores->avg() : 0;
                            $examScore = $subjectEvaluations->where('evaluation_type', 'exame')->first()?->score;

                            if ($nppScores->isEmpty() || ! $examScore) {
                                $allCompleted = false;
                                continue;
                            }

                            $subjectAverages[] = ($nppAverage * 0.4) + ((float) $examScore * 0.6);
                        }

                        $generalAverage = count($subjectAverages) ? array_sum($subjectAverages) / count($subjectAverages) : null;
                        $result = 'Pendente';

                        if ($subjectIds->isNotEmpty() && $generalAverage !== null && $allCompleted) {
                            $result = $generalAverage >= 10 ? 'Aprovado' : 'Reprovado';
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->candidate?->full_name ?? $record->full_name ?? '-' }}</td>
                        <td class="text-center">{{ $record->nuri ?? $record->nip ?? $record->student_number ?? '-' }}</td>
                        <td>{{ $record->courseMap?->course?->name ?? '-' }}</td>
                        <td class="text-center text-bold">{{ $generalAverage !== null ? number_format($generalAverage, 1) : '-' }}</td>
                        <td class="text-center">{{ $result }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
