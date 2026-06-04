@extends('reports.layout')
@section('title', 'Relatório de Dispensas e Faltas')
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
            <th>Tipo</th>
            <th>Motivo</th>
            <th>Data Início</th>
            <th>Data Fim</th>
            <th>Dias</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->student?->candidate?->full_name ?? '-' }}</td>
            <td><span class="badge {{ str_contains(strtolower($r->leave_type ?? ''), 'falta') ? 'badge-danger' : 'badge-warning' }}">{{ $r->leave_type ?? '-' }}</span></td>
            <td>{{ \Illuminate\Support\Str::limit($r->reason ?? '-', 40) }}</td>
            <td>{{ $r->start_date?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $r->end_date?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $r->start_date && $r->end_date ? $r->start_date->diffInDays($r->end_date) + 1 : '-' }}</td>
            <td><span class="badge {{ ($r->status ?? '') == 'approved' ? 'badge-success' : (($r->status ?? '') == 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($r->status ?? '-') }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection