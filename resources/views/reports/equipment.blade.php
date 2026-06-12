@extends('reports.layout')

@section('title', 'ATRIBUICAO DE MEIOS')
@section('subtitle', 'Equipamentos atribuidos aos formandos')

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
                    <th class="text-left" style="width: 24%;">Nome</th>
                    <th style="width: 11%;">NIP/NURI</th>
                    <th class="text-left" style="width: 32%;">Meios / Equipamentos</th>
                    <th style="width: 7%;">Qtd.</th>
                    <th style="width: 12%;">Datas</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['name'] }}</td>
                        <td class="text-center">{{ $record['number'] }}</td>
                        <td>
                            @forelse($record['equipment'] as $item)
                                <div>{{ $item['name'] }} ({{ $item['quantity'] }})</div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="text-center">{{ $record['quantity'] }}</td>
                        <td class="text-center">
                            @forelse($record['dates'] as $date)
                                <div>{{ $date }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="text-center">{{ $record['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
