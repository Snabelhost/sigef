@extends('reports.layout')

@section('title', 'FUNCIONARIOS')
@section('subtitle', 'Relacao geral dos funcionarios e efectivos cadastrados')

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
                    <th class="text-left" style="width: 22%;">Funcionario</th>
                    <th style="width: 10%;">NIP / BI</th>
                    <th style="width: 12%;">Cargo</th>
                    <th style="width: 16%;">Departamento</th>
                    <th style="width: 14%;">Unidade</th>
                    <th style="width: 12%;">Telefone</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->full_name }}</td>
                        <td class="text-center">{{ $record->employee_number ?: $record->nas ?: $record->identity_document ?: $record->document_number ?: '-' }}</td>
                        <td>{{ $record->position_label ?: $record->position ?: '-' }}</td>
                        <td>{{ $record->department ?: '-' }}</td>
                        <td>{{ $record->unit ?: $record->placement_organ ?: $record->institution?->acronym ?: '-' }}</td>
                        <td class="text-center">{{ $record->phone ?: '-' }}</td>
                        <td class="text-center">{{ $record->is_active ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
