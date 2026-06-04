<x-filament-panels::page>
    <style>
        /* ==================== Attendance Corporate Style ==================== */

        /* Header Section */
        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            flex: 1;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 200px;
        }

        .filter-group label {
            font-size: 0.7rem;
            font-weight: 600;
            color: rgb(var(--gray-500));
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
            color: rgb(var(--gray-900));
        }

        .dark .filter-input {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
            color: white;
        }

        .filter-input:focus {
            outline: none;
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 1px rgb(var(--primary-500));
        }

        /* Date Navigation */
        .date-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--gray-200));
        }

        .dark .date-nav {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-700));
        }

        .date-nav-btn {
            padding: 0.5rem;
            border: none;
            background: transparent;
            color: rgb(var(--gray-600));
            cursor: pointer;
            border-radius: 0.25rem;
            transition: all 0.15s;
        }

        .date-nav-btn:hover {
            background: rgb(var(--gray-100));
            color: rgb(var(--gray-900));
        }

        .dark .date-nav-btn:hover {
            background: rgb(var(--gray-700));
            color: white;
        }

        .current-date {
            font-weight: 600;
            font-size: 0.9rem;
            color: rgb(var(--gray-900));
            padding: 0 0.5rem;
            min-width: 160px;
            text-align: center;
        }

        .dark .current-date {
            color: white;
        }

        /* Week Mini Calendar */
        .week-mini {
            display: flex;
            gap: 0.25rem;
            background: white;
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--gray-200));
        }

        .dark .week-mini {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-700));
        }

        .week-day-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.375rem 0.5rem;
            border: 1px solid transparent;
            background: transparent;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s;
            min-width: 42px;
        }

        .week-day-btn:hover {
            background: rgb(var(--gray-100));
        }

        .dark .week-day-btn:hover {
            background: rgb(var(--gray-700));
        }

        .week-day-btn.selected {
            background: #041B4E;
            color: white;
        }

        .week-day-btn.today:not(.selected) {
            border: 2px solid #041B4E;
            background: #e8f4ff;
            position: relative;
        }

        .week-day-btn.today:not(.selected)::after {
            content: '';
            position: absolute;
            bottom: 2px;
            width: 6px;
            height: 6px;
            background: #041B4E;
            border-radius: 50%;
        }

        .week-day-btn.today .week-day-name,
        .week-day-btn.today:not(.selected) .week-day-name {
            color: #041B4E;
        }

        .week-day-btn.today:not(.selected) .week-day-number {
            color: #041B4E;
            font-weight: 800;
        }

        .dark .week-day-btn.selected {
            background: #3b82f6;
        }

        .dark .week-day-btn.today:not(.selected) {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.2);
        }

        .dark .week-day-btn.today:not(.selected)::after {
            background: #3b82f6;
        }

        .dark .week-day-btn.today:not(.selected) .week-day-name,
        .dark .week-day-btn.today:not(.selected) .week-day-number {
            color: #60a5fa;
        }

        .week-day-name {
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
            color: rgb(var(--gray-500));
        }

        .week-day-btn.selected .week-day-name {
            color: rgba(255, 255, 255, 0.8);
        }

        .week-day-number {
            font-size: 0.85rem;
            font-weight: 700;
            color: rgb(var(--gray-900));
        }

        .dark .week-day-number {
            color: white;
        }

        .week-day-btn.selected .week-day-number {
            color: white;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: rgb(var(--gray-50));
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--gray-200));
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .dark .stats-bar {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-700));
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .stat-icon.total {
            background: rgba(var(--gray-500), 0.1);
        }

        .stat-icon.present {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .stat-icon.absent {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .stat-icon.justified {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .stat-icon.late {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .stat-icon.unmarked {
            background: rgba(var(--gray-400), 0.15);
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: rgb(var(--gray-900));
        }

        .dark .stat-value {
            color: white;
        }

        .stat-label {
            font-size: 0.7rem;
            color: rgb(var(--gray-500));
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .action-btn {
            padding: 0.5rem 0.875rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid rgb(var(--gray-300));
            background: white;
            color: rgb(var(--gray-700));
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .dark .action-btn {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
            color: rgb(var(--gray-300));
        }

        .action-btn:hover {
            background: rgb(var(--gray-100));
        }

        .dark .action-btn:hover {
            background: rgb(var(--gray-700));
        }

        .action-btn.success {
            border-color: #10b981;
            color: #10b981;
        }

        .action-btn.success:hover {
            background: rgba(16, 185, 129, 0.1);
        }

        .action-btn.danger {
            border-color: #ef4444;
            color: #ef4444;
        }

        .action-btn.danger:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--gray-200));
            overflow: hidden;
        }

        .dark .table-container {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-700));
        }

        /* Attendance Table */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th {
            background: rgb(var(--gray-50));
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(var(--gray-600));
            border-bottom: 1px solid rgb(var(--gray-200));
        }

        .dark .attendance-table th {
            background: rgb(var(--gray-800));
            color: rgb(var(--gray-400));
            border-color: rgb(var(--gray-700));
        }

        .attendance-table td {
            padding: 0.625rem 1rem;
            border-bottom: 1px solid rgb(var(--gray-100));
            font-size: 0.85rem;
            color: rgb(var(--gray-900));
        }

        .dark .attendance-table td {
            border-color: rgb(var(--gray-800));
            color: rgb(var(--gray-100));
        }

        .attendance-table tr:last-child td {
            border-bottom: none;
        }

        .attendance-table tr:hover td {
            background: rgb(var(--gray-50));
        }

        .dark .attendance-table tr:hover td {
            background: rgb(var(--gray-800));
        }

        /* Student Cell */
        .student-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .student-number {
            width: 32px;
            height: 32px;
            background: rgb(var(--gray-100));
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: rgb(var(--gray-600));
        }

        .dark .student-number {
            background: rgb(var(--gray-700));
            color: rgb(var(--gray-300));
        }

        .student-name {
            font-weight: 500;
        }

        /* Status Buttons */
        .status-buttons {
            display: flex;
            gap: 0.25rem;
        }

        .status-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.25rem;
            border: 1px solid rgb(var(--gray-300));
            background: white;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 0.75rem;
            font-weight: 700;
            color: rgb(var(--gray-500));
        }

        .dark .status-btn {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
            color: rgb(var(--gray-400));
        }

        .status-btn:hover {
            transform: scale(1.05);
        }

        .status-btn.active.present {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .status-btn.active.absent {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }

        .status-btn.active.justified {
            background: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }

        .status-btn.active.late {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        /* Time Input */
        .time-input {
            width: 80px;
            padding: 0.375rem 0.5rem;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.25rem;
            font-size: 0.8rem;
            text-align: center;
            background: white;
            color: rgb(var(--gray-900));
        }

        .dark .time-input {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
            color: white;
        }

        .time-input:focus {
            outline: none;
            border-color: rgb(var(--primary-500));
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: rgb(var(--gray-500));
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgb(var(--gray-900));
            margin-bottom: 0.5rem;
        }

        .dark .empty-title {
            color: white;
        }

        .empty-icon-svg {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .empty-icon-svg svg {
            opacity: 0.6;
            width: 80px !important;
            height: 80px !important;
            max-width: 80px !important;
            max-height: 80px !important;
            min-width: 80px !important;
            min-height: 80px !important;
        }

        .empty-description {
            color: rgb(var(--gray-500));
            font-size: 0.9rem;
            margin: 0;
        }

        /* Table Info */
        .table-info {
            padding: 0.75rem 1rem;
            background: rgb(var(--gray-50));
            border-top: 1px solid rgb(var(--gray-200));
            font-size: 0.75rem;
            color: rgb(var(--gray-600));
            display: flex;
            justify-content: space-between;
        }

        .dark .table-info {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-700));
            color: rgb(var(--gray-400));
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid rgb(var(--gray-200));
            padding: 1rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .stat-card {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-700));
        }

        .stat-card-inner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stat-icon-wrapper {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .stat-icon-wrapper svg {
            width: 24px !important;
            height: 24px !important;
            color: white;
            stroke: white;
        }

        .stat-icon-wrapper.bg-slate {
            background: linear-gradient(to bottom right, #64748b, #475569);
        }

        .stat-icon-wrapper.bg-emerald {
            background: linear-gradient(to bottom right, #10b981, #059669);
        }

        .stat-icon-wrapper.bg-red {
            background: linear-gradient(to bottom right, #ef4444, #dc2626);
        }

        .stat-icon-wrapper.bg-amber {
            background: linear-gradient(to bottom right, #f59e0b, #d97706);
        }

        .stat-icon-wrapper.bg-blue {
            background: linear-gradient(to bottom right, #3b82f6, #2563eb);
        }

        .stat-icon-wrapper.bg-gray {
            background: linear-gradient(to bottom right, #9ca3af, #6b7280);
        }

        .stat-content .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-content .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgb(var(--gray-500));
        }

        .dark .stat-content .stat-label {
            color: rgb(var(--gray-400));
        }

        .stat-value.text-slate {
            color: rgb(var(--gray-900));
        }

        .stat-value.text-emerald {
            color: #10b981;
        }

        .stat-value.text-red {
            color: #ef4444;
        }

        .stat-value.text-amber {
            color: #f59e0b;
        }

        .stat-value.text-blue {
            color: #3b82f6;
        }

        .stat-value.text-gray {
            color: #6b7280;
        }

        .dark .stat-value.text-slate {
            color: white;
        }

        .dark .stat-value.text-emerald {
            color: #34d399;
        }

        .dark .stat-value.text-red {
            color: #f87171;
        }

        .dark .stat-value.text-amber {
            color: #fbbf24;
        }

        .dark .stat-value.text-blue {
            color: #60a5fa;
        }

        .dark .stat-value.text-gray {
            color: #9ca3af;
        }

        /* Quick Actions */
        .quick-actions-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .quick-actions-bar .percentage-badge {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .dark .quick-actions-bar .percentage-badge {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .percentage-badge svg {
            width: 14px !important;
            height: 14px !important;
        }
    </style>

    {{-- Filtros e Navegação --}}
    <div class="attendance-header">
        <div class="filters-row">
            @if(!\Filament\Facades\Filament::getTenant())
            <div class="filter-group">
                <label>Escola de Formação</label>
                <select class="filter-input" wire:model.live="selectedInstitutionId">
                    <option value="">Selecione...</option>
                    @foreach($this->institutions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="filter-group">
                <label>CIA</label>
                <select class="filter-input" wire:model.live="selectedCia" {{ !$selectedInstitutionId ? 'disabled' : '' }}>
                    <option value="">{{ $selectedInstitutionId ? 'Selecione a CIA...' : 'Selecione escola primeiro' }}</option>
                    @foreach($this->cias as $cia)
                    @php
                    // Formata CIA: se for número, adiciona ordinal (3ª CIA), senão exibe como está
                    $ciaLabel = is_numeric($cia) ? $cia . 'ª CIA' : $cia;
                    @endphp
                    <option value="{{ $cia }}">{{ $ciaLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($selectedCia)
        {{-- Navegação de Data --}}
        <div class="date-nav">
            <button type="button" wire:click="previousDay" class="date-nav-btn" title="Dia anterior">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z" />
                </svg>
            </button>
            <span class="current-date">
                {{ \Carbon\Carbon::parse($selectedDate)->locale('pt')->isoFormat('ddd, DD [de] MMMM') }}
            </span>
            <button type="button" wire:click="nextDay" class="date-nav-btn" title="Próximo dia">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                </svg>
            </button>
            <button type="button" wire:click="goToToday" class="date-nav-btn" title="Ir para hoje" style="margin-left: 0.25rem; font-size: 0.7rem; font-weight: 600;">
                Hoje
            </button>
        </div>

        {{-- Mini Calendário Semanal --}}
        <div class="week-mini">
            @foreach($this->weekDays as $day)
            <button
                type="button"
                wire:click="selectDate('{{ $day['date'] }}')"
                class="week-day-btn {{ $day['is_selected'] ? 'selected' : '' }} {{ $day['is_today'] ? 'today' : '' }}">
                <span class="week-day-name">{{ $day['day_name'] }}</span>
                <span class="week-day-number">{{ $day['day_number'] }}</span>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    @if($selectedCia && $this->students->count() > 0)
    @php $stats = $this->getStats(); @endphp

    {{-- Barra de Estatísticas - CSS Vanilla --}}
    <div class="stats-grid">
        {{-- Total --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-slate">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-slate">{{ $stats['total'] }}</p>
                    <p class="stat-label">Total</p>
                </div>
            </div>
        </div>

        {{-- Presentes --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-emerald">{{ $stats['present'] }}</p>
                    <p class="stat-label">Presentes</p>
                </div>
            </div>
        </div>

        {{-- Faltas --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-red">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-red">{{ $stats['absent'] }}</p>
                    <p class="stat-label">Faltas</p>
                </div>
            </div>
        </div>

        {{-- Justificadas --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-amber">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-amber">{{ $stats['justified'] }}</p>
                    <p class="stat-label">Justificadas</p>
                </div>
            </div>
        </div>

        {{-- Atrasos --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-blue">{{ $stats['late'] }}</p>
                    <p class="stat-label">Atrasos</p>
                </div>
            </div>
        </div>

        {{-- Pendentes --}}
        <div class="stat-card">
            <div class="stat-card-inner">
                <div class="stat-icon-wrapper bg-gray">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-gray">{{ $stats['unmarked'] }}</p>
                    <p class="stat-label">Pendentes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Ações Rápidas - Botões Filament --}}
    <div class="quick-actions-bar">
        <x-filament::button
            wire:click="markAllPresent"
            icon="heroicon-o-check-circle"
            size="sm"
            style="background-color: #041842 !important; border-color: #041842 !important; color: white !important;">
            Todos Presente
        </x-filament::button>

        <x-filament::button
            wire:click="markAllAbsent"
            color="danger"
            icon="heroicon-o-x-circle"
            size="sm">
            Todos Falta
        </x-filament::button>

        <x-filament::button
            wire:click="clearAll"
            color="gray"
            icon="heroicon-o-trash"
            size="sm"
            onclick="return confirm('Limpar todos os registos do dia?')">
            Limpar
        </x-filament::button>

        <span class="percentage-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            {{ $stats['percentage'] }}% Presença
        </span>
    </div>

    {{-- Tabela de Presenças --}}
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="min-width: 250px;">Nome do Aluno</th>
                    <th style="width: 130px;">NURI / NIP</th>
                    <th style="width: 180px;">Estado</th>
                    <th style="width: 150px;">Entrada</th>
                    <th style="width: 150px;">Saída</th>
                    <th style="min-width: 300px;">Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->students as $index => $student)
                @php
                $data = $attendanceData[$student->id] ?? [];
                $status = $data['status'] ?? null;
                // Buscar NURI/NIP do aluno
                $nuriNip = $student->nuri ?? '-';
                @endphp
                <tr>
                    <td>
                        <div class="student-cell">
                            <span class="student-name">{{ $student->full_name }}</span>
                        </div>
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; color: rgb(var(--gray-600)); font-weight: 500;">{{ $nuriNip }}</span>
                    </td>
                    <td>
                        <div class="status-buttons">
                            <button
                                type="button"
                                wire:click="setStatus({{ $student->id }}, 'P')"
                                class="status-btn {{ $status === 'P' ? 'active present' : '' }}"
                                title="Presente">P</button>
                            <button
                                type="button"
                                wire:click="setStatus({{ $student->id }}, 'F')"
                                class="status-btn {{ $status === 'F' ? 'active absent' : '' }}"
                                title="Falta">F</button>
                            <button
                                type="button"
                                wire:click="setStatus({{ $student->id }}, 'J')"
                                class="status-btn {{ $status === 'J' ? 'active justified' : '' }}"
                                title="Justificado">J</button>
                            <button
                                type="button"
                                wire:click="setStatus({{ $student->id }}, 'A')"
                                class="status-btn {{ $status === 'A' ? 'active late' : '' }}"
                                title="Atraso">A</button>
                        </div>
                    </td>
                    <td>
                        <input
                            type="time"
                            class="time-input"
                            style="width: 100%;"
                            value="{{ $data['entry_time'] ?? '' }}"
                            wire:blur="updateEntryTime({{ $student->id }}, $event.target.value)"
                            {{ !$status || $status === 'F' ? 'disabled' : '' }}>
                    </td>
                    <td>
                        <input
                            type="time"
                            class="time-input"
                            style="width: 100%;"
                            value="{{ $data['exit_time'] ?? '' }}"
                            wire:blur="updateExitTime({{ $student->id }}, $event.target.value)"
                            {{ !$status || $status === 'F' ? 'disabled' : '' }}>
                    </td>
                    <td>
                        <input
                            type="text"
                            class="filter-input"
                            style="width: 100%; font-size: 0.8rem;"
                            placeholder="Observação..."
                            value="{{ $data['observation'] ?? '' }}">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="table-info">
            @php
            $ciaLabel = is_numeric($selectedCia) ? $selectedCia . 'ª CIA' : $selectedCia;
            @endphp
            <span>{{ $ciaLabel }} • {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
            <span>{{ $stats['percentage'] }}% de presença</span>
        </div>
    </div>
    @elseif($selectedCia && $this->students->count() === 0)
    <div class="table-container">
        <div class="empty-state">
            <div class="empty-icon-svg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width: 80px; height: 80px; color: rgb(var(--gray-400));">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </div>
            <div class="empty-title">Nenhum Aluno Encontrado</div>
            <p class="empty-description">Esta CIA não possui alunos matriculados.</p>
        </div>
    </div>
    @else
    <div class="table-container">
        <div class="empty-state">
            <div class="empty-icon-svg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width: 80px; height: 80px; color: rgb(var(--gray-400));">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
            </div>
            <div class="empty-title">Selecione uma CIA</div>
            <p class="empty-description">Escolha a escola de formação e a CIA para registar presenças.</p>
        </div>
    </div>
    @endif
</x-filament-panels::page>