@extends('reports.layout')

@section('title', 'Relatório de '.$reportLabel)

@section('filters')
    <strong>Tipo:</strong> {{ $studentType }}
    @if($institution) | <strong>Escola:</strong> {{ $institution->name }} @endif
    @if($class) | <strong>Turma:</strong> {{ $class->name }} @endif
    @if($academicYear) | <strong>Ano Lectivo:</strong> {{ $academicYear->name }} @endif
    @if($dateFrom || $dateTo) | <strong>Período:</strong> {{ $dateFrom ?? 'Início' }} a {{ $dateTo ?? 'Hoje' }} @endif
@endsection

@section('summary')
    <span>Total: {{ $records->count() }}</span>
@endsection

@section('content')
    @if($records->count())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>NIP/NURI</th>
                    <th>BI</th>
                    <th>Escola</th>
                    <th>Curso</th>
                    <th>Turma</th>
                    <th>CIA</th>
                    <th>Pelotão</th>
                    <th>Secção</th>
                    <th>Estado/Tipo</th>
                    <th>Origem</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $i => $record)
                    @php
                        $type = $record['type'] ?: $studentType;
                        $normalizedType = mb_strtolower(\Illuminate\Support\Str::ascii($type));
                        $badgeClass = 'badge-gray';

                        if (str_contains($normalizedType, 'recruta')) {
                            $badgeClass = 'badge-warning';
                        } elseif (str_contains($normalizedType, 'instruendo')) {
                            $badgeClass = 'badge-info';
                        } elseif (str_contains($normalizedType, 'formacao') || str_contains($normalizedType, 'formando') || str_contains($normalizedType, 'conclu')) {
                            $badgeClass = 'badge-success';
                        }
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $record['name'] ?: '-' }}</td>
                        <td>{{ $record['number'] ?: '-' }}</td>
                        <td>{{ $record['bi'] ?: '-' }}</td>
                        <td>{{ $record['institution'] ?: '-' }}</td>
                        <td>{{ $record['course'] ?: '-' }}</td>
                        <td>{{ $record['class'] ?: '-' }}</td>
                        <td>{{ $record['cia'] ?: '-' }}</td>
                        <td>{{ $record['platoon'] ?: '-' }}</td>
                        <td>{{ $record['section'] ?: '-' }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $type ?: '-' }}</span></td>
                        <td>{{ $record['origin'] ?: '-' }}</td>
                        <td>{{ $record['date'] ? $record['date']->format('d/m/Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">Sem registos encontrados.</p>
    @endif
@endsection
