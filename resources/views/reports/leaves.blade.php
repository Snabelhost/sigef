@extends('reports.layout')

@section('title', 'DISPENSAS E FALTAS')
@section('subtitle', 'Registo de ocorrencias dos formandos')

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Periodo:</strong> {{ $dateFrom ?? 'Inicio' }} a {{ $dateTo ?? 'Hoje' }} @endif
    @if(! $institution && ! $dateFrom && ! $dateTo) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">N</th>
                    <th class="text-left" style="width: 21%;">Nome</th>
                    <th style="width: 10%;">NIP/NURI</th>
                    <th class="text-left" style="width: 45%;">Ocorrencias</th>
                    <th style="width: 7%;">Total</th>
                    <th style="width: 6%;">Dias</th>
                    <th style="width: 7%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['name'] }}</td>
                        <td class="text-center">{{ $record['number'] }}</td>
                        <td>
                            @forelse($record['occurrences'] as $occurrence)
                                <div>
                                    <strong>{{ $occurrence['type'] }}</strong>
                                    | {{ $occurrence['period'] }}
                                    | {{ $occurrence['days'] ? $occurrence['days'].' dia'.($occurrence['days'] === 1 ? '' : 's') : '-' }}
                                    | {{ \Illuminate\Support\Str::limit($occurrence['reason'], 80) }}
                                </div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="text-center">{{ $record['occurrence_count'] }}</td>
                        <td class="text-center">{{ $record['total_days'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['statuses'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
