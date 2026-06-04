@extends('reports.layout')
@section('title', 'Relatório de Cursos')
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Escola</th>
            <th>Duração (Meses)</th>
            <th>Fases</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->duration_months ?? '-' }}</td>
            <td><span class="badge {{ $r->has_phases ? 'badge-info' : 'badge-gray' }}">{{ $r->has_phases ? 'Sim' : 'Não' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection