@extends('reports.layout')

@section('title', 'LISTA DETALHADA DE FORMANDOS POR ORGAOS')
@section('subtitle', 'Relacao nominal por provincia e orgao de proveniencia')

@section('filters')
    @if($institution) <strong>Escola:</strong> {{ $institution->name }} @else <strong>Escola:</strong> Todas @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">Nº</th>
                    <th style="width: 10%;">Provincia</th>
                    <th class="text-left" style="width: 18%;">Orgao</th>
                    <th class="text-left" style="width: 25%;">Nome do formando</th>
                    <th style="width: 5%;">Sexo</th>
                    <th style="width: 5%;">Idade</th>
                    <th style="width: 8%;">NIP/NURI</th>
                    <th style="width: 9%;">Patente</th>
                    <th style="width: 12%;">Curso</th>
                    <th style="width: 4%;">Turma</th>
                </tr>
            </thead>
            <tbody>
                @php($currentProvince = null)
                @foreach($records as $index => $record)
                    @if($currentProvince !== $record['province'])
                        @php($currentProvince = $record['province'])
                        <tr class="group-row">
                            <td colspan="10">{{ $currentProvince }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $record['province'] }}</td>
                        <td>{{ $record['provenance'] }}</td>
                        <td>{{ $record['name'] }}</td>
                        <td class="text-center">{{ $record['gender'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['age'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['nip'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['rank'] ?: '-' }}</td>
                        <td>{{ $record['course'] ?: '-' }}</td>
                        <td class="text-center">{{ $record['class'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
