@extends('reports.layout')
@section('title', 'Relatório de Documentos')
@section('filters')
@if($dateFrom || $dateTo) <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Referência</th>
            <th>Remetente</th>
            <th>Prioridade</th>
            <th>Estado</th>
            <th>Data Envio</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->title ?? '-' }}</td>
            <td>{{ $r->reference_number ?? '-' }}</td>
            <td>{{ $r->senderInstitution?->acronym ?? '-' }}</td>
            <td><span class="badge {{ ($r->priority ?? '') == 'urgent' ? 'badge-danger' : (($r->priority ?? '') == 'confidential' ? 'badge-warning' : 'badge-info') }}">{{ ucfirst($r->priority ?? '-') }}</span></td>
            <td><span class="badge {{ ($r->status ?? '') == 'sent' ? 'badge-success' : (($r->status ?? '') == 'draft' ? 'badge-gray' : 'badge-info') }}">{{ ucfirst($r->status ?? '-') }}</span></td>
            <td>{{ $r->sent_at?->format('d/m/Y') ?? ($r->created_at?->format('d/m/Y') ?? '-') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection