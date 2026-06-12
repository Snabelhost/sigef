@extends('reports.layout')

@section('title', 'INSTITUICOES')
@section('subtitle', 'Lista de instituicoes registadas')

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">N</th>
                    <th class="text-left" style="width: 31%;">Nome</th>
                    <th style="width: 11%;">Sigla</th>
                    <th style="width: 17%;">Tipo</th>
                    <th class="text-left" style="width: 22%;">Localizacao</th>
                    <th style="width: 14%;">Contacto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record['name'] }}</td>
                        <td class="text-center">{{ $record['acronym'] }}</td>
                        <td class="text-center">{{ $record['type'] }}</td>
                        <td>{{ $record['location'] }}</td>
                        <td class="text-center">{{ $record['contact'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
