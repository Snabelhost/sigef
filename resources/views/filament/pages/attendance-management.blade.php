<x-filament-panels::page>
    <style>
        .fi-page:has(.sigef-attendance-page) > .fi-header {
            display: none !important;
        }

        .sigef-attendance-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .attendance-control {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: start;
            padding: 1rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .dark .attendance-control,
        .dark .attendance-card,
        .dark .table-container {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
        }

        .attendance-main-controls {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            min-width: 0;
        }

        .attendance-tabs {
            display: inline-grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            width: min(44rem, 100%);
            gap: 0.25rem;
            padding: 0.25rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            background: rgb(var(--gray-50));
        }

        .dark .attendance-tabs {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-800));
        }

        .attendance-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            min-height: 2.85rem;
            padding: 0.68rem 1rem;
            border: 0;
            border-radius: 0.375rem;
            background: transparent;
            color: rgb(var(--gray-600));
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .attendance-tab svg {
            width: 1.25rem;
            height: 1.25rem;
            flex: 0 0 auto;
        }

        .attendance-tab:hover {
            background: white;
            color: #041B4E;
            transform: translateY(-1px);
        }

        .attendance-tab.is-active {
            background: #041B4E;
            color: white;
            box-shadow: 0 6px 14px rgba(4, 27, 78, 0.18);
        }

        .dark .attendance-tab {
            color: rgb(var(--gray-300));
        }

        .dark .attendance-tab:hover {
            background: rgb(var(--gray-900));
            color: white;
        }

        .attendance-filters {
            display: grid;
            grid-template-columns: repeat(3, minmax(15rem, 1fr));
            gap: 0.75rem;
            max-width: 72rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 0;
        }

        .filter-group label,
        .mini-label {
            color: rgb(var(--gray-500));
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dark .filter-group label,
        .dark .mini-label {
            color: rgb(var(--gray-400));
        }

        .filter-input,
        .time-input,
        .observation-input {
            width: 100%;
            min-height: 2.35rem;
            padding: 0.5rem 0.7rem;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.45rem;
            background: white;
            color: rgb(var(--gray-900));
            font-size: 0.875rem;
            line-height: 1.25;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .dark .filter-input,
        .dark .time-input,
        .dark .observation-input {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
            color: white;
        }

        .filter-input:focus,
        .time-input:focus,
        .observation-input:focus {
            outline: none;
            border-color: #1a48ab;
            box-shadow: 0 0 0 2px rgba(26, 72, 171, 0.16);
        }

        .filter-input:disabled,
        .time-input:disabled,
        .observation-input:disabled {
            cursor: not-allowed;
            opacity: 0.58;
        }

        .filter-selectbox {
            position: relative;
            min-width: 0;
        }

        .filter-select-button {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            min-height: 2.35rem;
            padding: 0.5rem 0.62rem 0.5rem 0.7rem;
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.45rem;
            background: white;
            color: rgb(var(--gray-900));
            font-size: 0.875rem;
            line-height: 1.25;
            text-align: left;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .filter-select-button:hover,
        .filter-select-button.is-open {
            border-color: #1a48ab;
            box-shadow: 0 0 0 2px rgba(26, 72, 171, 0.12);
        }

        .filter-select-button:disabled {
            cursor: not-allowed;
            opacity: 0.58;
        }

        .filter-select-value {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .filter-select-placeholder {
            color: rgb(var(--gray-400));
        }

        .filter-select-chevron {
            width: 1rem;
            height: 1rem;
            color: rgb(var(--gray-500));
            transition: transform 0.15s ease;
        }

        .filter-select-button.is-open .filter-select-chevron {
            transform: rotate(180deg);
        }

        .filter-select-clear {
            position: absolute;
            top: 50%;
            right: 2rem;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.2rem;
            height: 1.2rem;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: rgb(var(--gray-400));
            transform: translateY(-50%);
            cursor: pointer;
        }

        .filter-select-clear::before {
            content: "\00d7";
            font-size: 1.08rem;
            font-weight: 700;
            line-height: 1;
        }

        .filter-select-clear:hover {
            color: #041B4E;
        }

        .filter-select-panel {
            position: absolute;
            top: calc(100% + 0.25rem);
            left: 0;
            right: 0;
            z-index: 60;
            overflow: hidden;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.55rem;
            background: white;
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.16);
        }

        .filter-select-search {
            width: 100%;
            min-height: 2.35rem;
            padding: 0.55rem 0.7rem;
            border: 0;
            border-bottom: 1px solid rgb(var(--gray-200));
            background: white;
            color: rgb(var(--gray-900));
            font-size: 0.875rem;
            outline: none;
        }

        .filter-select-options {
            max-height: 13.5rem;
            overflow-y: auto;
            padding: 0.25rem;
        }

        .filter-select-option,
        .filter-select-empty {
            width: 100%;
            padding: 0.55rem 0.6rem;
            border: 0;
            border-radius: 0.4rem;
            background: transparent;
            color: rgb(var(--gray-800));
            font-size: 0.86rem;
            font-weight: 700;
            line-height: 1.25;
            text-align: left;
        }

        .filter-select-option {
            cursor: pointer;
        }

        .filter-select-option:hover,
        .filter-select-option.is-selected {
            background: rgba(26, 72, 171, 0.1);
            color: #041B4E;
        }

        .filter-select-empty {
            color: rgb(var(--gray-500));
        }

        .dark .filter-select-button,
        .dark .filter-select-panel,
        .dark .filter-select-search {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
            color: white;
        }

        .dark .filter-select-option,
        .dark .filter-select-empty {
            color: rgb(var(--gray-200));
        }

        .attendance-date-panel {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            align-items: flex-end;
            min-width: 23rem;
        }

        .date-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            background: rgb(var(--gray-50));
        }

        .dark .date-nav {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-800));
        }

        .date-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.35rem;
            min-height: 2.35rem;
            padding: 0.35rem 0.55rem;
            border: 0;
            border-radius: 0.375rem;
            background: transparent;
            color: rgb(var(--gray-700));
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .date-nav-btn svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .date-nav-btn:hover {
            background: white;
            color: #041B4E;
        }

        .dark .date-nav-btn {
            color: rgb(var(--gray-300));
        }

        .dark .date-nav-btn:hover {
            background: rgb(var(--gray-900));
            color: white;
        }

        .current-date {
            min-width: 12rem;
            padding: 0 0.4rem;
            color: rgb(var(--gray-950));
            font-size: 0.9rem;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
        }

        .dark .current-date {
            color: white;
        }

        .week-mini {
            display: grid;
            grid-template-columns: repeat(5, minmax(2.7rem, 1fr));
            gap: 0.25rem;
            width: 100%;
        }

        .week-day-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 3.15rem;
            padding: 0.35rem 0.45rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.45rem;
            background: white;
            color: rgb(var(--gray-700));
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
        }

        .dark .week-day-btn {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-950));
            color: rgb(var(--gray-300));
        }

        .week-day-btn:hover {
            border-color: #1a48ab;
            color: #041B4E;
        }

        .week-day-btn.is-selected {
            border-color: #041B4E;
            background: #041B4E;
            color: white;
        }

        .week-day-btn.is-today:not(.is-selected) {
            border-color: #1a48ab;
            background: rgba(26, 72, 171, 0.08);
            color: #1a48ab;
        }

        .week-day-name {
            font-size: 0.64rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .week-day-number {
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.05;
        }

        .attendance-summary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
        }

        .context-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
            min-width: 0;
        }

        .context-pill,
        .percentage-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            min-height: 2rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgb(var(--gray-100));
            color: rgb(var(--gray-700));
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .dark .context-pill {
            background: rgb(var(--gray-800));
            color: rgb(var(--gray-300));
        }

        .context-pill.primary {
            background: rgba(26, 72, 171, 0.1);
            color: #1a48ab;
        }

        .context-pill.success,
        .percentage-badge {
            background: rgba(5, 150, 105, 0.1);
            color: #047857;
        }

        .quick-actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-items: center;
            justify-content: flex-end;
        }

        .attendance-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            min-height: 2.8rem;
            padding: 0.72rem 1.05rem;
            border: 1px solid transparent;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.86rem;
            font-weight: 900;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        }

        .attendance-action-btn svg {
            width: 1.55rem;
            height: 1.55rem;
            flex: 0 0 auto;
            stroke-width: 2.25;
        }

        .attendance-action-btn span {
            white-space: nowrap;
        }

        .attendance-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.14);
            filter: brightness(1.02);
        }

        .attendance-action-btn:active {
            transform: translateY(0);
        }

        .attendance-action-btn.primary {
            background: #041B4E;
            border-color: #041B4E;
        }

        .attendance-action-btn.danger {
            background: #dc2626;
            border-color: #dc2626;
        }

        .attendance-action-btn.neutral {
            background: white;
            border-color: rgb(var(--gray-300));
            color: rgb(var(--gray-700));
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
        }

        .attendance-action-btn.neutral svg {
            color: #4b5563;
        }

        .dark .attendance-action-btn.neutral {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-700));
            color: rgb(var(--gray-200));
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .stat-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.65rem;
            align-items: center;
            min-height: 4.8rem;
            padding: 0.85rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .dark .stat-card {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-900));
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            border-radius: 0.5rem;
            background: rgb(var(--gray-100));
            color: rgb(var(--gray-600));
        }

        .stat-icon svg {
            width: 1.35rem;
            height: 1.35rem;
            stroke-width: 2.2;
        }

        .stat-icon.present { background: rgba(5, 150, 105, 0.12); color: #059669; }
        .stat-icon.absent { background: rgba(220, 38, 38, 0.12); color: #dc2626; }
        .stat-icon.justified { background: rgba(217, 119, 6, 0.12); color: #d97706; }
        .stat-icon.late { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
        .stat-icon.unmarked { background: rgba(107, 114, 128, 0.12); color: #6b7280; }

        .stat-value {
            color: rgb(var(--gray-950));
            font-size: 1.35rem;
            font-weight: 900;
            line-height: 1;
        }

        .dark .stat-value {
            color: white;
        }

        .stat-label {
            margin-top: 0.28rem;
            color: rgb(var(--gray-500));
            font-size: 0.73rem;
            font-weight: 800;
        }

        .stat-value.present { color: #059669; }
        .stat-value.absent { color: #dc2626; }
        .stat-value.justified { color: #d97706; }
        .stat-value.late { color: #2563eb; }
        .stat-value.unmarked { color: #6b7280; }

        .attendance-card,
        .table-container {
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .attendance-card {
            overflow: hidden;
        }

        .table-container {
            overflow: hidden;
        }

        .attendance-table-wrap {
            max-height: calc(100vh - 22rem);
            min-height: 16rem;
            overflow: auto;
        }

        .attendance-table {
            width: 100%;
            min-width: 960px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .attendance-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 0.78rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.14);
            background: #041B4E;
            color: white;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .attendance-table td {
            padding: 0.72rem 1rem;
            border-bottom: 1px solid rgb(var(--gray-100));
            color: rgb(var(--gray-900));
            font-size: 0.86rem;
            vertical-align: middle;
        }

        .dark .attendance-table td {
            border-color: rgb(var(--gray-800));
            color: rgb(var(--gray-100));
        }

        .attendance-table tr:last-child td {
            border-bottom: 0;
        }

        .attendance-table tbody tr {
            transition: background 0.14s ease;
        }

        .attendance-table tbody tr:hover {
            background: rgb(var(--gray-50));
        }

        .dark .attendance-table tbody tr:hover {
            background: rgb(var(--gray-800));
        }

        .person-cell {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .person-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 999px;
            background: #041B4E;
            color: white;
            font-size: 0.78rem;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .person-name {
            color: rgb(var(--gray-950));
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .dark .person-name {
            color: white;
        }

        .person-subline,
        .muted-text {
            color: rgb(var(--gray-500));
            font-size: 0.76rem;
            font-weight: 700;
        }

        .status-buttons {
            display: inline-flex;
            gap: 0.3rem;
            padding: 0.24rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 0.65rem;
            background: rgb(var(--gray-50));
        }

        .dark .status-buttons {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-800));
        }

        .status-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border: 0;
            border-radius: 0.52rem;
            background: transparent;
            color: rgb(var(--gray-600));
            font-size: 0.88rem;
            font-weight: 900;
            cursor: pointer;
            box-shadow: none;
            transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }

        .status-btn svg {
            width: 1.25rem;
            height: 1.25rem;
            stroke-width: 2.35;
        }

        .status-btn:hover {
            background: white;
            color: #041B4E;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
        }

        .status-btn.is-present {
            background: #059669;
            color: white;
            box-shadow: 0 8px 16px rgba(5, 150, 105, 0.18);
        }

        .status-btn.is-absent {
            background: #dc2626;
            color: white;
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.18);
        }

        .status-btn.is-justified {
            background: #d97706;
            color: white;
            box-shadow: 0 8px 16px rgba(217, 119, 6, 0.18);
        }

        .status-btn.is-late {
            background: #2563eb;
            color: white;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
        }

        .time-input {
            min-height: 2.15rem;
            max-width: 7.5rem;
            text-align: center;
        }

        .observation-input {
            min-width: 14rem;
            min-height: 2.15rem;
            font-size: 0.82rem;
        }

        .table-info {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-top: 1px solid rgb(var(--gray-200));
            background: rgb(var(--gray-50));
            color: rgb(var(--gray-600));
            font-size: 0.8rem;
            font-weight: 800;
        }

        .dark .table-info {
            border-color: rgb(var(--gray-700));
            background: rgb(var(--gray-800));
            color: rgb(var(--gray-300));
        }

        .progress-line {
            width: 9rem;
            height: 0.45rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(5, 150, 105, 0.16);
        }

        .progress-line span {
            display: block;
            width: var(--attendance-progress);
            height: 100%;
            border-radius: inherit;
            background: #059669;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 18rem;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .empty-icon-svg {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            margin-bottom: 0.85rem;
            border-radius: 999px;
            background: rgba(26, 72, 171, 0.1);
            color: #1a48ab;
        }

        .empty-title {
            color: rgb(var(--gray-950));
            font-size: 1rem;
            font-weight: 900;
        }

        .dark .empty-title {
            color: white;
        }

        .empty-description {
            max-width: 32rem;
            margin-top: 0.45rem;
            color: rgb(var(--gray-500));
            font-size: 0.875rem;
            line-height: 1.45;
        }

        @media (max-width: 1180px) {
            .attendance-control,
            .attendance-summary-row {
                grid-template-columns: 1fr;
            }

            .attendance-date-panel {
                align-items: stretch;
                min-width: 0;
            }

            .date-nav {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .attendance-tabs,
            .attendance-filters,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .attendance-control {
                padding: 0.85rem;
            }

            .week-mini {
                grid-template-columns: repeat(5, minmax(2.25rem, 1fr));
            }

            .current-date {
                min-width: 0;
            }

            .quick-actions-bar {
                justify-content: flex-start;
            }

            .table-info {
                flex-direction: column;
            }
        }
    </style>

    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('attendanceSearchSelect', (config) => ({
                    options: config.options || [],
                    value: config.value,
                    placeholder: config.placeholder || 'Selecione...',
                    searchPlaceholder: config.searchPlaceholder || 'Pesquisar...',
                    disabled: Boolean(config.disabled),
                    search: '',
                    open: false,

                    init() {
                        this.syncSearch();
                        this.$watch('value', () => this.syncSearch());
                    },

                    get selectedOption() {
                        return this.options.find((option) => String(option.value) === String(this.value));
                    },

                    get selectedLabel() {
                        return this.selectedOption ? this.selectedOption.label : '';
                    },

                    get filteredOptions() {
                        const term = this.normalize(this.search);

                        if (!term) {
                            return this.options;
                        }

                        return this.options.filter((option) => this.normalize(option.label).includes(term));
                    },

                    normalize(value) {
                        return String(value || '')
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '');
                    },

                    toggle() {
                        if (this.disabled) {
                            return;
                        }

                        this.open = !this.open;

                        if (this.open) {
                            this.search = '';
                            this.$nextTick(() => this.$refs.searchInput?.focus());
                        }
                    },

                    close() {
                        this.open = false;
                        this.syncSearch();
                    },

                    choose(option) {
                        this.value = option.value;
                        this.search = option.label;
                        this.open = false;
                    },

                    clear() {
                        this.value = null;
                        this.search = '';
                        this.open = false;
                    },

                    syncSearch() {
                        this.search = this.selectedLabel;
                    },
                }));
            });
        </script>
    @endonce

    @php
        $contextReady = $this->contextIsReady();
        $people = $this->attendancePeople;
        $stats = $this->getStats();
        $identifierLabel = match ($activeTab) {
            'trainers' => 'NIP/BI',
            'effectives' => 'Nº/NAS',
            default => 'NIP/NURI',
        };
        $needsInstitution = $this->needsInstitutionFilter();
        $contextSuffix = '';
        $toSelectOptions = fn ($options) => collect($options)
            ->map(fn ($label, $value) => [
                'value' => is_numeric($value) ? (int) $value : (string) $value,
                'label' => (string) $label,
            ])
            ->values()
            ->all();

        $institutionOptions = $toSelectOptions($this->institutions);
        $academicYearOptions = $toSelectOptions($this->academicYears);
        $courseOptions = $toSelectOptions($this->courses);
        $ciaOptions = $toSelectOptions(
            collect($this->cias)->mapWithKeys(fn ($cia) => [
                $cia => is_numeric($cia) ? $cia . 'ª CIA' : $cia,
            ])
        );
        $platoonOptions = $toSelectOptions(
            collect($this->platoons)->mapWithKeys(fn ($platoon) => [
                $platoon => is_numeric($platoon) ? $platoon . 'º PELOTÃO' : $platoon,
            ])
        );
        $trainerSubjectOptions = $toSelectOptions($this->trainerSubjects);
        $effectiveUnitOptions = $toSelectOptions($this->effectiveUnits);

        if ($activeTab === 'students') {
            $contextParts = [];

            if (filled($selectedCia)) {
                $contextParts[] = is_numeric($selectedCia) ? $selectedCia . 'ª CIA' : $selectedCia;
            }

            if (filled($selectedPlatoon)) {
                $contextParts[] = is_numeric($selectedPlatoon) ? $selectedPlatoon . 'º PELOTÃO' : $selectedPlatoon;
            }

            $contextSuffix = $contextParts ? ' / ' . implode(' / ', $contextParts) : '';
        }
    @endphp

    <div class="sigef-attendance-page">
        <div class="attendance-control">
            <div class="attendance-main-controls">
                <div class="attendance-tabs" role="tablist" aria-label="Tipos de presença">
                    <button type="button" wire:click="setActiveTab('students')" @class(['attendance-tab', 'is-active' => $activeTab === 'students'])>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                        </svg>
                        Formandos
                    </button>
                    <button type="button" wire:click="setActiveTab('trainers')" @class(['attendance-tab', 'is-active' => $activeTab === 'trainers'])>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347M4.26 10.147 12 5.25l7.74 4.897M4.26 10.147 12 15.044l7.74-4.897" />
                        </svg>
                        Professores
                    </button>
                    @if($this->attendanceTabIsVisible('effectives'))
                        <button type="button" wire:click="setActiveTab('effectives')" @class(['attendance-tab', 'is-active' => $activeTab === 'effectives'])>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25a2.1 2.1 0 0 1-2.1 2.1H5.85a2.1 2.1 0 0 1-2.1-2.1v-4.25M16.5 6.75V5.4a1.9 1.9 0 0 0-1.9-1.9H9.4a1.9 1.9 0 0 0-1.9 1.9v1.35M3.75 8.85h16.5v5.3H3.75v-5.3Z" />
                            </svg>
                            Efectivos
                        </button>
                    @endif
                </div>

                @if($needsInstitution || in_array($activeTab, ['students', 'trainers', 'effectives'], true))
                    <div class="attendance-filters">
                        @if($needsInstitution)
                            <div class="filter-group">
                                <label>Escola de Formação</label>
                                <div
                                    wire:key="attendance-filter-institution-{{ count($institutionOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($institutionOptions), value: @entangle('selectedInstitutionId').live, placeholder: 'Selecione...', searchPlaceholder: 'Pesquisar escola...', disabled: false })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Escola de Formação"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'trainers')
                            <div class="filter-group">
                                <label>Nome do Professor</label>
                                <input
                                    type="search"
                                    class="filter-input"
                                    wire:model.live.debounce.400ms="trainerSearch"
                                    placeholder="Pesquisar professor..."
                                >
                            </div>

                            <div class="filter-group">
                                <label>Ano Lectivo</label>
                                <div
                                    wire:key="attendance-trainer-filter-academic-year-{{ $selectedInstitutionId ?: 'none' }}-{{ count($academicYearOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($academicYearOptions), value: @entangle('selectedAcademicYearId').live, placeholder: '{{ $selectedInstitutionId ? 'Selecione o ano...' : 'Selecione escola primeiro' }}', searchPlaceholder: 'Pesquisar ano lectivo...', disabled: {{ $selectedInstitutionId ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Ano Lectivo"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>Curso</label>
                                <div
                                    wire:key="attendance-trainer-filter-course-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ count($courseOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($courseOptions), value: @entangle('selectedCourseId').live, placeholder: '{{ $selectedAcademicYearId ? 'Selecione o curso...' : 'Selecione ano primeiro' }}', searchPlaceholder: 'Pesquisar curso...', disabled: {{ ($selectedInstitutionId && $selectedAcademicYearId) ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Curso"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>CIA</label>
                                <div
                                    wire:key="attendance-trainer-filter-cia-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ $selectedCourseId ?: 'none' }}-{{ count($ciaOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($ciaOptions), value: @entangle('selectedCia').live, placeholder: '{{ $selectedCourseId ? 'Selecione a CIA...' : 'Selecione curso primeiro' }}', searchPlaceholder: 'Pesquisar CIA...', disabled: {{ ($selectedInstitutionId && $selectedAcademicYearId && $selectedCourseId) ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar CIA"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>Disciplina</label>
                                <div
                                    wire:key="attendance-trainer-filter-subject-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ $selectedCourseId ?: 'none' }}-{{ md5((string) $selectedCia) }}-{{ count($trainerSubjectOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($trainerSubjectOptions), value: @entangle('selectedSubjectId').live, placeholder: '{{ $selectedInstitutionId ? 'Selecione a disciplina...' : 'Selecione escola primeiro' }}', searchPlaceholder: 'Pesquisar disciplina...', disabled: {{ $selectedInstitutionId ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Disciplina"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'effectives')
                            <div class="filter-group">
                                <label>Pesquisar Efectivo</label>
                                <input
                                    type="search"
                                    class="filter-input"
                                    wire:model.live.debounce.400ms="effectiveSearch"
                                    placeholder="Nome, NIP ou BI"
                                >
                            </div>

                            <div class="filter-group">
                                <label>Unidade / Departamento</label>
                                <div
                                    wire:key="attendance-effective-filter-unit-{{ $selectedInstitutionId ?: 'none' }}-{{ count($effectiveUnitOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($effectiveUnitOptions), value: @entangle('selectedEffectiveUnit').live, placeholder: 'Todos os efectivos', searchPlaceholder: 'Pesquisar unidade/departamento...', disabled: {{ $selectedInstitutionId ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Unidade / Departamento"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'students')
                            <div class="filter-group">
                                <label>Ano Lectivo</label>
                                <div
                                    wire:key="attendance-filter-academic-year-{{ $selectedInstitutionId ?: 'none' }}-{{ count($academicYearOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($academicYearOptions), value: @entangle('selectedAcademicYearId').live, placeholder: '{{ $selectedInstitutionId ? 'Selecione o ano...' : 'Selecione escola primeiro' }}', searchPlaceholder: 'Pesquisar ano lectivo...', disabled: {{ $selectedInstitutionId ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Ano Lectivo"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>Curso</label>
                                <div
                                    wire:key="attendance-filter-course-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ count($courseOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($courseOptions), value: @entangle('selectedCourseId').live, placeholder: '{{ $selectedAcademicYearId ? 'Selecione o curso...' : 'Selecione ano primeiro' }}', searchPlaceholder: 'Pesquisar curso...', disabled: {{ ($selectedInstitutionId && $selectedAcademicYearId) ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Curso"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>CIA</label>
                                <div
                                    wire:key="attendance-filter-cia-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ $selectedCourseId ?: 'none' }}-{{ count($ciaOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($ciaOptions), value: @entangle('selectedCia').live, placeholder: '{{ $selectedCourseId ? 'Selecione a CIA...' : 'Selecione curso primeiro' }}', searchPlaceholder: 'Pesquisar CIA...', disabled: {{ ($selectedInstitutionId && $selectedAcademicYearId && $selectedCourseId) ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar CIA"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>Pelotão</label>
                                <div
                                    wire:key="attendance-filter-platoon-{{ $selectedInstitutionId ?: 'none' }}-{{ $selectedAcademicYearId ?: 'none' }}-{{ $selectedCourseId ?: 'none' }}-{{ md5((string) $selectedCia) }}-{{ count($platoonOptions) }}"
                                    class="filter-selectbox"
                                    x-data="attendanceSearchSelect({ options: @js($platoonOptions), value: @entangle('selectedPlatoon').live, placeholder: '{{ filled($selectedCia) ? 'Selecione o pelotão...' : 'Selecione CIA primeiro' }}', searchPlaceholder: 'Pesquisar pelotão...', disabled: {{ ($selectedInstitutionId && $selectedAcademicYearId && $selectedCourseId && filled($selectedCia)) ? 'false' : 'true' }} })"
                                    x-on:click.outside="close()"
                                >
                                    <button type="button" class="filter-select-button" x-bind:class="{ 'is-open': open }" x-on:click="toggle()" x-bind:disabled="disabled">
                                        <span class="filter-select-value" x-bind:class="{ 'filter-select-placeholder': !selectedLabel }" x-text="selectedLabel || placeholder"></span>
                                        <svg class="filter-select-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <button type="button" class="filter-select-clear" x-show="value !== null && value !== '' && !disabled" x-on:click.stop="clear()" aria-label="Limpar Pelotão"></button>
                                    <div class="filter-select-panel" x-show="open" x-transition>
                                        <input x-ref="searchInput" class="filter-select-search" type="search" x-model="search" x-bind:placeholder="searchPlaceholder" x-on:keydown.escape.prevent="close()">
                                        <div class="filter-select-options">
                                            <template x-for="option in filteredOptions" x-bind:key="option.value">
                                                <button type="button" class="filter-select-option" x-bind:class="{ 'is-selected': String(option.value) === String(value) }" x-on:click="choose(option)" x-text="option.label"></button>
                                            </template>
                                            <div class="filter-select-empty" x-show="filteredOptions.length === 0">Nenhum resultado encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="attendance-date-panel">
                <div class="date-nav">
                    <button type="button" wire:click="previousDay" class="date-nav-btn" title="Dia anterior" aria-label="Dia anterior">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z" />
                        </svg>
                    </button>
                    <span class="current-date">
                        {{ \Carbon\Carbon::parse($selectedDate)->locale('pt')->isoFormat('ddd, DD [de] MMMM') }}
                    </span>
                    <button type="button" wire:click="nextDay" class="date-nav-btn" title="Próximo dia" aria-label="Próximo dia">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                    </button>
                    <button type="button" wire:click="goToToday" class="date-nav-btn" title="Ir para hoje">Hoje</button>
                </div>

                <div class="week-mini">
                    @foreach($this->weekDays as $day)
                        <button
                            type="button"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            @class(['week-day-btn', 'is-selected' => $day['is_selected'], 'is-today' => $day['is_today']])
                        >
                            <span class="week-day-name">{{ $day['day_name'] }}</span>
                            <span class="week-day-number">{{ $day['day_number'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @if($contextReady && $people->count() > 0)
            <div class="attendance-summary-row">
                <div class="context-strip">
                    <span class="context-pill primary">{{ $this->activeTabLabel() }}{{ $contextSuffix }}</span>
                    <span class="context-pill">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                    <span class="context-pill success">{{ $people->count() }} registo(s)</span>
                </div>

                <div class="quick-actions-bar">
                    <button
                        type="button"
                        wire:click="markAllPresent"
                        class="attendance-action-btn primary"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Todos Presente</span>
                    </button>

                    <button
                        type="button"
                        wire:click="markAllAbsent"
                        class="attendance-action-btn danger"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Todos Falta</span>
                    </button>

                    <button
                        type="button"
                        wire:click="clearAll"
                        class="attendance-action-btn neutral"
                        onclick="return confirm('Limpar todos os registos do dia?')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-8 0 1 13h8l1-13" />
                        </svg>
                        <span>Limpar</span>
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584m12-4.894A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon present">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value present">{{ $stats['present'] }}</div>
                        <div class="stat-label">Presentes</div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon absent">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value absent">{{ $stats['absent'] }}</div>
                        <div class="stat-label">Faltas</div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon justified">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75M12 15.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value justified">{{ $stats['justified'] }}</div>
                        <div class="stat-label">Justificadas</div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon late">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value late">{{ $stats['late'] }}</div>
                        <div class="stat-label">Atrasos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon unmarked">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75v5.25l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="stat-value unmarked">{{ $stats['unmarked'] }}</div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="attendance-table-wrap">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th style="min-width: 290px;">Nome</th>
                                <th style="width: 145px;">{{ $identifierLabel }}</th>
                                <th style="width: 170px;">Presença</th>
                                <th style="width: 130px;">Entrada</th>
                                <th style="width: 130px;">Saída</th>
                                <th style="min-width: 260px;">Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($people as $person)
                                @php
                                    $data = $attendanceData[$person->id] ?? [];
                                    $status = $data['status'] ?? null;
                                @endphp
                                <tr wire:key="attendance-{{ $activeTab }}-{{ $person->id }}">
                                    <td>
                                        <div class="person-cell">
                                            <span class="person-avatar">{{ $this->personInitials($person) }}</span>
                                            <div>
                                                <div class="person-name">{{ $this->personName($person) }}</div>
                                                <div class="person-subline">{{ $this->personContext($person) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="muted-text">{{ $this->personIdentifier($person) }}</span>
                                    </td>
                                    <td>
                                        <div class="status-buttons">
                                            <button type="button" wire:click="setStatus({{ $person->id }}, 'P')" @class(['status-btn', 'is-present' => $status === 'P']) title="Presente" aria-label="Presente">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18.75 19.5 5.25" />
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $person->id }}, 'F')" @class(['status-btn', 'is-absent' => $status === 'F']) title="Falta" aria-label="Falta">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 6.75 10.5 10.5m0-10.5-10.5 10.5" />
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $person->id }}, 'J')" @class(['status-btn', 'is-justified' => $status === 'J']) title="Justificado" aria-label="Justificado">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 19.5 7.5v4.5c0 4.1-2.55 7.4-7.5 9-4.95-1.6-7.5-4.9-7.5-9V7.5L12 3.75Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12.5 1.8 1.8 3.7-4.1" />
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $person->id }}, 'A')" @class(['status-btn', 'is-late' => $status === 'A']) title="Atraso" aria-label="Atraso">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            class="time-input"
                                            value="{{ $data['entry_time'] ?? '' }}"
                                            wire:blur="updateEntryTime({{ $person->id }}, $event.target.value)"
                                            {{ !$status || $status === 'F' ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            class="time-input"
                                            value="{{ $data['exit_time'] ?? '' }}"
                                            wire:blur="updateExitTime({{ $person->id }}, $event.target.value)"
                                            {{ !$status || $status === 'F' ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="observation-input"
                                            placeholder="Observação..."
                                            value="{{ $data['observation'] ?? '' }}"
                                            wire:blur="updateObservation({{ $person->id }}, $event.target.value)"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-info">
                    <span>{{ $this->activeTabLabel() }}{{ $contextSuffix }} / {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                    <span style="display: inline-flex; align-items: center; gap: 0.65rem;">
                        {{ $stats['percentage'] }}% de presença
                        <span class="progress-line" style="--attendance-progress: {{ $stats['percentage'] }}%;">
                            <span></span>
                        </span>
                    </span>
                </div>
            </div>
        @elseif($contextReady)
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon-svg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="empty-title">Nenhum registo encontrado</div>
                    <p class="empty-description">Não existem {{ mb_strtolower($this->activeTabLabel()) }} para o contexto selecionado.</p>
                </div>
            </div>
        @else
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon-svg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M9 3.75h6A2.25 2.25 0 0 1 17.25 6v12A2.25 2.25 0 0 1 15 20.25H9A2.25 2.25 0 0 1 6.75 18V6A2.25 2.25 0 0 1 9 3.75Z" />
                        </svg>
                    </div>
                    <div class="empty-title">Aguardando seleção</div>
                    <p class="empty-description">
                        @if($activeTab === 'students')
                            Selecione Escola de Formação, Ano Lectivo, Curso, CIA e Pelotão para carregar os formandos.
                        @else
                            Selecione a escola para carregar {{ mb_strtolower($this->activeTabLabel()) }}.
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
