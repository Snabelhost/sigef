@extends('reports.layout')
@section('title', 'Relatório de Instituições')
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Sigla</th>
            <th>Tipo</th>
            <th>Localização</th>
            <th>Contacto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->acronym ?? '-' }}</td>
            <td>{{ $r->institutionType?->name ?? $r->type ?? '-' }}</td>
            <td>{{ $r->location ?? $r->address ?? '-' }}</td>
            <td>{{ $r->phone ?? $r->email ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection