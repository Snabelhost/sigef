@extends('reports.layout')
@section('title', 'Relatório de Cadetes')
@section('filters')
@if($institution) <strong>Escola:</strong> {{ $institution->name }} @endif
@if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
@if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nº</th>
            <th>Nome</th>
            <th>Escola</th>
            <th>Mapa de Curso</th>
            <th>Estado</th>
            <th>Data Inscrição</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->student_number ?? '-' }}</td>
            <td>{{ $r->candidate?->full_name ?? '-' }}</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->courseMap?->name ?? '-' }}</td>
            <td><span class="badge {{ $r->status == 'frequenta' ? 'badge-success' : ($r->status == 'desistiu' ? 'badge-danger' : 'badge-gray') }}">{{ ucfirst($r->status ?? '-') }}</span></td>
            <td>{{ $r->enrollment_date?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection