@extends('reports.layout')

@section('title', 'DISCIPLINAS')
@section('subtitle', 'Lista de disciplinas')

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width: 48%;">Disciplina</th>
                    <th style="width: 16%;">Carga Horaria</th>
                    <th style="width: 18%;">Tipo</th>
                    <th style="width: 18%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        <td>{{ $record->name }}</td>
                        <td class="text-center">{{ $record->workload_hours ? $record->workload_hours.'h' : '-' }}</td>
                        <td class="text-center">{{ $record->type ?? 'Obrigatoria' }}</td>
                        <td class="text-center">Activo</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
