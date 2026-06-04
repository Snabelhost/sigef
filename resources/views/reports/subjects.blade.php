@extends('reports.layout')
@section('title', 'Relatório de Disciplinas')
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Carga Horária</th>
            <th>Escola</th>
            <th>Fase</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->workload_hours ?? '-' }}h</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->coursePhase?->name ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection