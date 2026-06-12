@extends('reports.layout')

@section('title', 'AVALIACOES DE APOIO')
@section('subtitle', 'Notas lancadas por formando e disciplina')

@section('filters')
    @if($institution) <strong>Instituicao:</strong> {{ $institution->name }} @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
    @if(! $institution && ! $class) <strong>Filtros:</strong> Todos os registos @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">N</th>
                    <th class="text-left" style="width: 23%;">Nome</th>
                    <th style="width: 10%;">NIP/NURI</th>
                    <th class="text-left" style="width: 49%;">Avaliacoes</th>
                    <th style="width: 6%;">Total</th>
                    <th style="width: 8%;">Media</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['name'] }}</td>
                        <td class="text-center">{{ $record['number'] }}</td>
                        <td>
                            @forelse(collect($record['evaluations'] ?? []) as $evaluation)
                                <div>
                                    <strong>{{ $evaluation['subject'] }}</strong>
                                    | {{ $evaluation['type'] }}
                                    | <span class="text-bold">{{ $evaluation['score'] }}</span>
                                    | {{ $evaluation['date'] }}
                                </div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="text-center">{{ $record['evaluation_count'] }}</td>
                        <td class="text-center text-bold">{{ $record['average'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
