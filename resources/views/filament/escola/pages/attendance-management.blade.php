<x-filament-panels::page>
    <style>
        /* Variables */
        :root {
            --attendance-present: #10b981;
            --attendance-absent: #ef4444;
            --attendance-justified: #f59e0b;
            --attendance-late: #3b82f6;
            --bg-dark: #1e293b;
            --bg-card: #0f172a;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }

        .attendance-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
            min-height: 600px;
        }

        @media (max-width: 1024px) {
            .attendance-container {
                grid-template-columns: 1fr;
            }
        }

        /* Calendar Section */
        .calendar-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-btn.today {
            background: var(--attendance-present);
            border-color: var(--attendance-present);
        }

        /* Week Days Header */
        .week-header {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .day-header {
            text-align: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .day-header.is-today {
            border-color: var(--attendance-present);
            background: rgba(16, 185, 129, 0.1);
        }

        .day-name {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .day-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .day-month {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* Attendance Grid for Selected Student */
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }

        .attendance-cell {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
        }

        .attendance-cell:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .attendance-cell.present {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--attendance-present);
            color: var(--attendance-present);
        }

        .attendance-cell.absent {
            background: rgba(239, 68, 68, 0.2);
            border-color: var(--attendance-absent);
            color: var(--attendance-absent);
        }

        .attendance-cell.justified {
            background: rgba(245, 158, 11, 0.2);
            border-color: var(--attendance-justified);
            color: var(--attendance-justified);
        }

        /* Selected Student Info */
        .selected-student-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .selected-student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
        }

        .selected-student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .selected-student-number {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-dot.present { background: var(--attendance-present); }
        .legend-dot.absent { background: var(--attendance-absent); }

        /* Students Section */
        .students-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            max-height: 700px;
            display: flex;
            flex-direction: column;
        }

        .students-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .students-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .students-count {
            font-size: 0.875rem;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .students-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .student-card {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            gap: 0.75rem;
        }

        .student-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-color);
        }

        .student-card.selected {
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }

        .student-card.all-present {
            border-left: 3px solid var(--attendance-present);
        }

        .student-card.has-absence {
            border-left: 3px solid var(--attendance-absent);
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #475569, #64748b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            color: white;
            flex-shrink: 0;
        }

        .student-info {
            flex: 1;
            min-width: 0;
        }

        .student-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-number {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .student-stats {
            display: flex;
            gap: 0.25rem;
        }

        .stat-badge {
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .stat-badge.present {
            background: rgba(16, 185, 129, 0.2);
            color: var(--attendance-present);
        }

        .stat-badge.absent {
            background: rgba(239, 68, 68, 0.2);
            color: var(--attendance-absent);
        }

        /* No Selection State */
        .no-selection {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: var(--text-secondary);
            text-align: center;
        }

        .no-selection-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-selection-text {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .no-selection-hint {
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            flex: 1;
            min-width: 120px;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .quick-action-btn.success {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--attendance-present);
        }

        /* Mark All Button */
        .mark-all-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .mark-all-btn {
            padding: 0.5rem;
            font-size: 0.7rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px dashed var(--attendance-present);
            color: var(--attendance-present);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mark-all-btn:hover {
            background: rgba(16, 185, 129, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>

    {{-- Form de Filtros --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    @if($this->selectedClassId)
        <div class="attendance-container">
            {{-- Calendário / Marcação --}}
            <div class="calendar-section">
                <div class="calendar-header">
                    <h3 class="calendar-title">
                        Semana: {{ \Carbon\Carbon::parse($selectedWeekStart)->format('d/m') }} - 
                        {{ \Carbon\Carbon::parse($selectedWeekStart)->addDays(4)->format('d/m/Y') }}
                    </h3>
                    <div class="calendar-nav">
                        <button type="button" wire:click="previousWeek" class="nav-btn">
                            ← Anterior
                        </button>
                        <button type="button" wire:click="currentWeek" class="nav-btn today">
                            Hoje
                        </button>
                        <button type="button" wire:click="nextWeek" class="nav-btn">
                            Próxima →
                        </button>
                    </div>
                </div>

                {{-- Cabeçalho dos Dias --}}
                <div class="week-header">
                    @foreach($weekDays as $day)
                        <div class="day-header {{ $day['is_today'] ? 'is-today' : '' }}">
                            <div class="day-name">{{ $day['day_name'] }}</div>
                            <div class="day-number">{{ $day['day_number'] }}</div>
                            <div class="day-month">{{ $day['month'] }}</div>
                        </div>
                    @endforeach
                </div>

                @if($selectedStudentId)
                    @php
                        $student = $this->students->firstWhere('id', $selectedStudentId);
                        $initials = $student ? collect(explode(' ', $student->full_name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('') : '?';
                    @endphp

                    {{-- Info do Aluno Selecionado --}}
                    <div class="selected-student-info">
                        <div class="selected-student-avatar">
                            {{ $initials }}
                        </div>
                        <div>
                            <div class="selected-student-name">{{ $student->full_name ?? 'Aluno' }}</div>
                            <div class="selected-student-number">Nº {{ $student->student_number ?? '-' }}</div>
                        </div>
                    </div>

                    {{-- Grid de Marcação --}}
                    <div class="attendance-grid">
                        @foreach($weekDays as $day)
                            @php
                                $key = "{$selectedStudentId}_{$day['date']}";
                                $status = $attendanceData[$key] ?? null;
                                $statusClass = match($status) {
                                    'P' => 'present',
                                    'F' => 'absent',
                                    'J' => 'justified',
                                    default => '',
                                };
                                $statusLabel = match($status) {
                                    'P' => 'P',
                                    'F' => 'F',
                                    'J' => 'J',
                                    default => '—',
                                };
                            @endphp
                            <div 
                                class="attendance-cell {{ $statusClass }}"
                                wire:click="toggleAttendance({{ $selectedStudentId }}, '{{ $day['date'] }}', {{ $status ? "'$status'" : 'null' }})"
                                title="Clique para alternar: {{ $day['day_name'] }} {{ $day['day_number'] }}"
                            >
                                {{ $statusLabel }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Acções Rápidas --}}
                    <div class="quick-actions">
                        @foreach($weekDays as $day)
                            <button 
                                type="button" 
                                wire:click="setAttendance({{ $selectedStudentId }}, '{{ $day['date'] }}', 'P')"
                                class="quick-action-btn success"
                            >
                                ✓ {{ $day['day_name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Legenda --}}
                    <div class="legend">
                        <div class="legend-item">
                            <span class="legend-dot present"></span>
                            <span>P = Presente</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot absent"></span>
                            <span>F = Falta</span>
                        </div>
                        <div class="legend-item" style="color: var(--text-secondary); font-size: 0.75rem;">
                            💡 Clique na célula para alternar
                        </div>
                    </div>
                @else
                    {{-- Nenhum Aluno Selecionado --}}
                    <div class="no-selection">
                        <div class="no-selection-icon">👆</div>
                        <div class="no-selection-text">Selecione um aluno</div>
                        <div class="no-selection-hint">Clique num aluno da lista à direita para marcar presenças</div>
                    </div>

                    {{-- Marcar Todos Presente --}}
                    <div class="mark-all-row">
                        @foreach($weekDays as $day)
                            <button 
                                type="button" 
                                wire:click="markAllPresent('{{ $day['date'] }}')"
                                class="mark-all-btn"
                            >
                                ✓ Todos P
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Lista de Alunos --}}
            <div class="students-section">
                <div class="students-header">
                    <h3 class="students-title">Lista de Alunos</h3>
                    <span class="students-count">{{ $this->students->count() }} alunos</span>
                </div>

                <div class="students-list">
                    @forelse($this->students as $student)
                        @php
                            $stats = $this->getStudentAttendanceStats($student->id);
                            $initials = collect(explode(' ', $student->full_name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                            $cardClass = $stats['absent'] > 0 ? 'has-absence' : ($stats['present'] > 0 ? 'all-present' : '');
                        @endphp
                        <div 
                            class="student-card {{ $selectedStudentId === $student->id ? 'selected' : '' }} {{ $cardClass }}"
                            wire:click="selectStudent({{ $student->id }})"
                        >
                            <div class="student-avatar">{{ $initials }}</div>
                            <div class="student-info">
                                <div class="student-name">{{ $student->full_name }}</div>
                                <div class="student-number">Nº {{ $student->student_number ?? '-' }}</div>
                            </div>
                            <div class="student-stats">
                                @if($stats['present'] > 0)
                                    <span class="stat-badge present">{{ $stats['present'] }}P</span>
                                @endif
                                @if($stats['absent'] > 0)
                                    <span class="stat-badge absent">{{ $stats['absent'] }}F</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <p>Nenhum aluno inscrito nesta turma</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="empty-state" style="background: var(--bg-card); border-radius: 16px; padding: 4rem; border: 1px solid var(--border-color);">
            <div class="empty-state-icon">📋</div>
            <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Selecione uma Turma</h3>
            <p style="color: var(--text-secondary);">Escolha uma turma no filtro acima para começar a marcar presenças</p>
        </div>
    @endif
</x-filament-panels::page>
