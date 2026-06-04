@extends('reports.layout')
@section('title', 'Relatório de Planos de Curso')
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Curso</th>
            <th>Ano Lectivo</th>
            <th>Nº Disciplinas</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->course?->name ?? '-' }}</td>
            <td>{{ $r->academicYear?->name ?? '-' }}</td>
            <td>{{ $r->subjects->count() }}</td>
            <td><span class="badge {{ $r->is_active ? 'badge-success' : 'badge-gray' }}">{{ $r->is_active ? 'Activo' : 'Inactivo' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection