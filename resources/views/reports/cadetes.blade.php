@extends('reports.layout')

@section('title', 'FORMANDOS')
@section('subtitle', 'Lista de formandos inscritos')

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $institution && ! $class && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">N</th>
                    <th style="width: 12%;">NIP/NURI</th>
                    <th class="text-left" style="width: 30%;">Nome</th>
                    <th style="width: 14%;">Instituicao</th>
                    <th class="text-left" style="width: 23%;">Curso</th>
                    <th style="width: 8%;">Estado</th>
                    <th style="width: 8%;">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $record->nuri ?? $record->nip ?? $record->student_number ?? '-' }}</td>
                        <td>{{ $record->candidate?->full_name ?? '-' }}</td>
                        <td class="text-center">{{ $record->institution?->acronym ?? $record->institution?->name ?? '-' }}</td>
                        <td>{{ $record->courseMap?->course?->name ?? $record->courseMap?->name ?? '-' }}</td>
                        <td class="text-center">{{ \Illuminate\Support\Str::headline((string) ($record->status ?? '-')) }}</td>
                        <td class="text-center">{{ $record->enrollment_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
