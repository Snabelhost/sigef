@extends('reports.layout')
@section('title', 'Relatório de Utilizadores')
@section('filters')
@if($dateFrom || $dateTo)
<strong>Período:</strong> {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Início' }} a {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Hoje' }}
@endif
@endsection
@section('summary')
<span>Total de Utilizadores: {{ $records->count() }}</span>
@endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Perfil</th>
            <th>Data Criação</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->email }}</td>
            <td>{{ $r->getRoleNames()->implode(', ') ?: '-' }}</td>
            <td>{{ $r->created_at?->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection