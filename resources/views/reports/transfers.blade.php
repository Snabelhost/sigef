@extends('reports.layout')

@section('title', 'HISTORICO DE TRANSFERENCIAS')
@section('subtitle', 'Movimentos de formandos entre instituicoes')

@section('filters')
    @if($dateFrom || $dateTo) <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">N</th>
                    <th class="text-left" style="width: 20%;">Nome</th>
                    <th style="width: 10%;">NIP/NURI</th>
                    <th class="text-left" style="width: 20%;">De</th>
                    <th class="text-left" style="width: 20%;">Para</th>
                    <th class="text-left" style="width: 16%;">Motivo</th>
                    <th style="width: 10%;">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    @php
                        $student = $record->student;
                        $candidate = $student?->candidate;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $candidate?->full_name ?? $student?->full_name ?? '-' }}</td>
                        <td class="text-center">{{ $student?->nuri ?? $student?->nip ?? $student?->student_number ?? '-' }}</td>
                        <td>{{ $record->fromInstitution?->name ?? $record->from_institution ?? '-' }}</td>
                        <td>{{ $record->toInstitution?->name ?? $record->to_institution ?? '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($record->reason ?? $record->observation ?? '-', 70) }}</td>
                        <td class="text-center">{{ $record->transfer_date ? \Carbon\Carbon::parse($record->transfer_date)->format('d/m/Y') : ($record->created_at?->format('d/m/Y') ?? '-') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
