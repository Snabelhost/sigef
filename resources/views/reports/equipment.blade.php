@extends('reports.layout')
@section('title', 'Relatório de Atribuição de Meios')
@section('filters')
@if($institution) <strong>Escola:</strong> {{ $institution->name }} @endif
@if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Formando</th>
            <th>Equipamento</th>
            <th>Quantidade</th>
            <th>Data Atrib.</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->student?->candidate?->full_name ?? '-' }}</td>
            <td>{{ $r->equipment_name ?? $r->description ?? '-' }}</td>
            <td>{{ $r->quantity ?? 1 }}</td>
            <td>{{ $r->assigned_at ? \Carbon\Carbon::parse($r->assigned_at)->format('d/m/Y') : ($r->created_at?->format('d/m/Y') ?? '-') }}</td>
            <td><span class="badge {{ ($r->status ?? 'active') == 'active' ? 'badge-success' : 'badge-gray' }}">{{ ucfirst($r->status ?? 'Activo') }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection