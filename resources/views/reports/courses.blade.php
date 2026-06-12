@extends('reports.layout')

@section('title', 'CURSOS')
@section('subtitle', 'Lista de cursos disponiveis')

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Codigo</th>
                    <th class="text-left" style="width: 31%;">Curso</th>
                    <th style="width: 14%;">Grau</th>
                    <th style="width: 12%;">Duracao</th>
                    <th class="text-left" style="width: 23%;">Faculdade/Escola</th>
                    <th style="width: 8%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        <td class="text-center">CUR-{{ str_pad((string) $record->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $record->name }}</td>
                        <td class="text-center">{{ $record->degree ?? $record->level ?? '-' }}</td>
                        <td class="text-center">{{ $record->duration_months ? $record->duration_months.' meses' : '-' }}</td>
                        <td>{{ $record->institution?->name ?? '-' }}</td>
                        <td class="text-center">{{ $record->has_phases ? 'Activo' : 'Activo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
