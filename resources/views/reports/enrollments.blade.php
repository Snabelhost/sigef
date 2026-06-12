@extends('reports.layout')

@section('title', 'LISTAS NOMINAIS DAS TURMAS')
@section('subtitle', 'Relacao nominal dos formandos por turma')

@section('filters')
    @if($class) <strong>Turma:</strong> {{ $class->name }} @else <strong>Turma:</strong> Todas @endif
    @if($institution) | <strong>Escola:</strong> {{ $institution->name }} @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">Nº</th>
                    <th class="text-left" style="width: 32%;">Nome</th>
                    <th style="width: 12%;">NIP/NURI</th>
                    <th class="text-left" style="width: 25%;">Curso</th>
                    <th style="width: 9%;">Turma</th>
                    <th style="width: 13%;">Ano lectivo</th>
                    <th style="width: 5%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    @php
                        $studentName = trim((string) ($record->candidate?->full_name ?: $record->full_name ?: ''));
                        $enrollment = $record->classEnrollments->firstWhere('is_active', true)
                            ?? $record->classEnrollments->sortByDesc('enrolled_at')->first();
                        $academicYear = $enrollment?->academicYear?->year
                            ?: $enrollment?->academicYear?->name
                            ?: $enrollment?->studentClass?->academicYear?->year
                            ?: $enrollment?->studentClass?->academicYear?->name
                            ?: $enrollment?->studentClass?->courseMap?->academicYear?->year
                            ?: $enrollment?->studentClass?->courseMap?->academicYear?->name
                            ?: $record->courseMap?->academicYear?->year
                            ?: $record->courseMap?->academicYear?->name
                            ?: $record->candidate?->academicYear?->year
                            ?: $record->candidate?->academicYear?->name
                            ?: '-';
                    @endphp
                    @continue($studentName === '' || $studentName === '-')
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $studentName }}</td>
                        <td class="text-center">{{ $record->nuri ?: $record->student_number ?: '-' }}</td>
                        <td>{{ $enrollment?->studentClass?->courseMap?->course?->name ?: $record->courseMap?->course?->name ?: '-' }}</td>
                        <td class="text-center">{{ $enrollment?->studentClass?->name ?? '-' }}</td>
                        <td class="text-center">{{ $academicYear }}</td>
                        <td class="text-center">{{ $record->student_type ?: $record->status ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
