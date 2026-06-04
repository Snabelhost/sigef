@extends('reports.layout')
@section('title', 'Relatório de Gestão de Formandos')
@section('filters')
@if($class) <strong>Turma:</strong> {{ $class->name }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Escola</th>
            <th>Curso</th>
            <th>Estado Actual</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        @php
        $type = $r->student_type ?? '-';
        $badgeClass = 'badge-gray';
        if (str_contains(strtolower($type), 'recruta')) $badgeClass = 'badge-warning';
        elseif (str_contains(strtolower($type), 'instruendo')) $badgeClass = 'badge-info';
        elseif (str_contains(strtolower($type), 'formação')) $badgeClass = 'badge-success';
        elseif (str_contains(strtolower($type), 'formando')) $badgeClass = 'badge-success';
        @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->candidate?->full_name ?? '-' }}</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->courseMap?->course?->name ?? '-' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $type }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection