@extends('reports.layout')
@section('title', 'Relatório de Transferências')
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
            <th>Formando</th>
            <th>Escola Origem</th>
            <th>Escola Destino</th>
            <th>Motivo</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->student?->candidate?->full_name ?? '-' }}</td>
            <td>{{ $r->fromInstitution?->acronym ?? $r->from_institution ?? '-' }}</td>
            <td>{{ $r->toInstitution?->acronym ?? $r->to_institution ?? '-' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($r->reason ?? $r->observation ?? '-', 40) }}</td>
            <td>{{ $r->transfer_date ? \Carbon\Carbon::parse($r->transfer_date)->format('d/m/Y') : ($r->created_at?->format('d/m/Y') ?? '-') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection