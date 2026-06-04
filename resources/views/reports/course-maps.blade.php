@extends('reports.layout')
@section('title', 'Relatório de Mapas de Curso')
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Curso</th>
            <th>Escola</th>
            <th>Ano Lectivo</th>
            <th>Órgão</th>
            <th>Data Início</th>
            <th>Data Fim</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->course?->name ?? '-' }}</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->academicYear?->name ?? '-' }}</td>
            <td>{{ $r->organ ?? '-' }}</td>
            <td>{{ $r->start_date?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $r->end_date?->format('d/m/Y') ?? '-' }}</td>
            <td><span class="badge {{ $r->is_active ? 'badge-success' : 'badge-gray' }}">{{ $r->is_active ? 'Activo' : 'Inactivo' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection