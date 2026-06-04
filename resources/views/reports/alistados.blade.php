@extends('reports.layout')
@section('title', 'Relatório de Alistados')
@section('filters')
@if($academicYear) <strong>Ano Lectivo:</strong> {{ $academicYear->name }} @endif
@if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Nº BI</th>
            <th>Sexo</th>
            <th>Proveniência</th>
            <th>Estado</th>
            <th>Data Alistamento</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->full_name }}</td>
            <td>{{ $r->id_number ?? '-' }}</td>
            <td>{{ $r->gender ?? '-' }}</td>
            <td>{{ $r->provenance?->name ?? '-' }}</td>
            <td><span class="badge {{ $r->status == 'approved' ? 'badge-success' : ($r->status == 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($r->status ?? '-') }}</span></td>
            <td>{{ $r->created_at?->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection