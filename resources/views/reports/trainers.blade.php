@extends('reports.layout')

@section('title', 'LISTA DOS PROFESSORES')
@section('subtitle', 'Relacao geral dos professores cadastrados')

@section('filters')
    @if($institution)
        <strong>Escola:</strong> {{ $institution->name }}
    @else
        <strong>Escola:</strong> Todas
    @endif
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">Nº</th>
                    <th class="text-left" style="width: 19%;">Professor</th>
                    <th style="width: 10%;">NIP / BI</th>
                    <th style="width: 7%;">Sexo</th>
                    <th style="width: 10%;">Patente</th>
                    <th style="width: 12%;">Grau academico</th>
                    <th style="width: 10%;">Situacao</th>
                    <th style="width: 12%;">Departamento</th>
                    <th style="width: 10%;">Unidade</th>
                    <th style="width: 6%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->full_name }}</td>
                        <td class="text-center">{{ $record->nip ?: $record->bilhete ?: '-' }}</td>
                        <td class="text-center">{{ $record->gender ?: '-' }}</td>
                        <td class="text-center">{{ $record->rank?->name ?? '-' }}</td>
                        <td class="text-center">{{ $record->education_level ?: '-' }}</td>
                        <td class="text-center">{{ $record->situation ?: $record->trainer_type ?: '-' }}</td>
                        <td>{{ $record->department ?: '-' }}</td>
                        <td>{{ $record->organ ?: $record->institution?->acronym ?: '-' }}</td>
                        <td class="text-center">{{ $record->is_active ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
