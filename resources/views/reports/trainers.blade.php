@extends('reports.layout')
@section('title', 'Relatório de Formadores')
@section('filters')
@if($institution) <strong>Escola:</strong> {{ $institution->name }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Posto</th>
            <th>NIP</th>
            <th>Escola</th>
            <th>Tipo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->full_name }}</td>
            <td>{{ $r->rank?->name ?? '-' }}</td>
            <td>{{ $r->nip ?? '-' }}</td>
            <td>{{ $r->institution?->acronym ?? '-' }}</td>
            <td>{{ $r->trainer_type ?? '-' }}</td>
            <td><span class="badge {{ $r->is_active ? 'badge-success' : 'badge-gray' }}">{{ $r->is_active ? 'Activo' : 'Inactivo' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection