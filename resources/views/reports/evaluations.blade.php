@extends('reports.layout')
@section('title', 'Relatório de Avaliações')
@section('filters')
@if($class) <strong>Turma:</strong> {{ $class->name }} @endif
@endsection
@section('summary') <span>Total: {{ $records->count() }}</span> @endsection
@section('content')
@if($records->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Formando</th>
            <th>Disciplina</th>
            <th>Nota</th>
            <th>Tipo</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $r->student?->candidate?->full_name ?? '-' }}</td>
            <td>{{ $r->subject?->name ?? '-' }}</td>
            <td class="text-bold">{{ $r->grade ?? $r->score ?? '-' }}</td>
            <td>{{ $r->type ?? $r->evaluation_type ?? '-' }}</td>
            <td>{{ $r->evaluation_date ? \Carbon\Carbon::parse($r->evaluation_date)->format('d/m/Y') : ($r->created_at?->format('d/m/Y') ?? '-') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else <p class="no-data">Sem registos encontrados.</p> @endif
@endsection