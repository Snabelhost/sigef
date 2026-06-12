@if($records->count())
    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 22%;">Curso</th>
                <th style="width: 11%;">Ano</th>
                <th style="width: 12%;">Semestre/Fase</th>
                <th class="text-left" style="width: 29%;">Disciplina</th>
                <th style="width: 8%;">Carga Horaria</th>
                <th style="width: 7%;">Obrigatoria</th>
                <th style="width: 7%;">Nota minima</th>
                <th style="width: 4%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record['course'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['year'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['phase'] ?: '-' }}</td>
                    <td>{{ $record['subject'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['workload'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['mandatory'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['minimum_grade'] ?: '-' }}</td>
                    <td class="text-center">{{ $record['state'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="no-data">Sem registos encontrados.</p>
@endif
