<div>
    @if($showModal)
    {!! '<template x-teleport="body">' !!}
        <div
            x-data="{ isOpen: true }"
            x-show="isOpen"
            x-cloak
            x-trap.noscroll="isOpen"
            class="fi-modal fi-absolute-positioning-context fi-stats-detail-modal"
            aria-modal="true"
            role="dialog">
            {{-- Overlay (usa classe nativa do Filament) --}}
            <div
                aria-hidden="true"
                x-show="isOpen"
                x-transition.duration.300ms.opacity
                class="fi-modal-close-overlay"></div>

            {{-- Container do modal (usa classe nativa do Filament) --}}
            <div
                x-on:click.self="isOpen = false; $wire.closeModal()"
                class="fi-modal-window-ctn fi-clickable">
                {{-- Window do modal --}}
                <div
                    x-show="isOpen"
                    x-on:keydown.escape.window="isOpen = false; $wire.closeModal()"
                    x-transition:enter="fi-transition-enter"
                    x-transition:leave="fi-transition-leave"
                    x-transition:enter-start="fi-transition-enter-start"
                    x-transition:enter-end="fi-transition-enter-end"
                    x-transition:leave-start="fi-transition-leave-start"
                    x-transition:leave-end="fi-transition-leave-end"
                    class="fi-modal-window fi-modal-window-has-close-btn fi-modal-window-has-content fi-width-7xl">
                    {{-- Header (usa classes nativas do Filament) --}}
                    <div class="fi-modal-header">
                        {{-- Close Button --}}
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="fi-modal-close-btn fi-icon-btn fi-icon-btn-size-lg"
                            tabindex="-1">
                            <svg class="fi-icon-btn-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div>
                            <h2 class="fi-modal-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                {{ $modalTitle }}
                            </h2>
                        </div>
                    </div>

                    {{-- Content (usa classe nativa do Filament) --}}
                    <div class="fi-modal-content">
                        {{-- Summary Stats (Filament-style) --}}
                        @if(count($summaryStats) > 0)
                        <div style="display: grid; grid-template-columns: repeat({{ count($summaryStats) }}, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                            @foreach($summaryStats as $stat)
                            @php
                            $statColor = match($stat['color']) {
                            'primary' => '#2563eb',
                            'success' => '#059669',
                            'danger' => '#dc2626',
                            'warning' => '#d97706',
                            'info' => '#0284c7',
                            default => '#6366f1',
                            };
                            $statIcon = match($stat['color']) {
                            'primary' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                            'success' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                            'danger' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
                            'warning' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                            'info' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
                            default => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z',
                            };
                            @endphp
                            <div style="position: relative; background: white; border-radius: 0.75rem; padding: 1.25rem 1rem 1.25rem 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #e5e7eb; overflow: hidden;">
                                {{-- Left accent bar --}}
                                <div style="position: absolute; top: 0; left: 0; bottom: 0; width: 4px; background: {{ $statColor }};"></div>
                                <div style="display: flex; align-items: center; gap: 1rem; padding-left: 0.75rem;">
                                    {{-- Icon --}}
                                    <div style="flex-shrink: 0; width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; background: {{ $statColor }}12; display: flex; align-items: center; justify-content: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $statColor }}" style="width: 1.25rem; height: 1.25rem;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $statIcon }}" />
                                        </svg>
                                    </div>
                                    {{-- Text --}}
                                    <div style="display: flex; flex-direction: column; min-width: 0;">
                                        <span style="font-size: 1.5rem; font-weight: 700; color: #111827; line-height: 1.2;">{{ number_format($stat['value'], 0, ',', '.') }}</span>
                                        <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-top: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $stat['label'] }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Charts Grid --}}
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem;"
                            x-data="statsCharts(@js($chartData), @js($modalType))"
                            x-init="initCharts()">
                            {{-- Bar / Stacked Bar Chart --}}
                            <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10" style="padding: 1.25rem; background: #fff;">
                                <h3 style="font-size: 0.8125rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                                    {{ $chartData['bar']['title'] ?? $chartData['stacked_bar']['title'] ?? 'Distribuição' }}
                                </h3>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>

                            {{-- Doughnut Chart --}}
                            @if(isset($chartData['doughnut']))
                            <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10" style="padding: 1.25rem; background: #fff;">
                                <h3 style="font-size: 0.8125rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                                    {{ $chartData['doughnut']['title'] ?? 'Distribuição' }}
                                </h3>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="doughnutChart"></canvas>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! '
    </template>' !!}
    @endif

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        Alpine.data('statsCharts', (chartData, modalType) => ({
            barChart: null,
            doughnutChart: null,

            initCharts() {
                this.$nextTick(() => {
                    this.destroyCharts();
                    this.createBarChart(chartData);
                    if (chartData.doughnut) {
                        this.createDoughnutChart(chartData.doughnut);
                    }
                });
            },

            destroyCharts() {
                if (this.barChart) {
                    this.barChart.destroy();
                    this.barChart = null;
                }
                if (this.doughnutChart) {
                    this.doughnutChart.destroy();
                    this.doughnutChart = null;
                }
            },

            createBarChart(data) {
                const ctx = document.getElementById('barChart');
                if (!ctx) return;

                let config;
                const fontFamily = "'Inter', system-ui, sans-serif";

                if (data.stacked_bar) {
                    config = {
                        type: 'bar',
                        data: {
                            labels: data.stacked_bar.labels,
                            datasets: data.stacked_bar.datasets.map(ds => ({
                                label: ds.label,
                                data: ds.values,
                                backgroundColor: ds.color,
                                borderRadius: 6,
                                borderSkipped: false,
                                barPercentage: 0.7,
                            })),
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 600,
                                easing: 'easeOutQuart'
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 11,
                                            family: fontFamily,
                                            weight: '500'
                                        },
                                        usePointStyle: true,
                                        padding: 16,
                                        boxHeight: 8
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: {
                                        family: fontFamily,
                                        size: 12
                                    },
                                    bodyFont: {
                                        family: fontFamily,
                                        size: 11
                                    },
                                    padding: 10,
                                    cornerRadius: 8,
                                    boxPadding: 4
                                },
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 9,
                                            family: fontFamily
                                        },
                                        maxRotation: 45
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,0,0,0.04)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 10,
                                            family: fontFamily
                                        },
                                        stepSize: 1
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                            },
                        },
                    };
                } else if (data.bar) {
                    config = {
                        type: 'bar',
                        data: {
                            labels: data.bar.labels,
                            datasets: [{
                                label: data.bar.title,
                                data: data.bar.values,
                                backgroundColor: data.bar.colors,
                                borderRadius: 8,
                                borderSkipped: false,
                                barPercentage: 0.65,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 600,
                                easing: 'easeOutQuart'
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: {
                                        family: fontFamily,
                                        size: 12
                                    },
                                    bodyFont: {
                                        family: fontFamily,
                                        size: 11
                                    },
                                    padding: 10,
                                    cornerRadius: 8,
                                    boxPadding: 4
                                },
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 9,
                                            family: fontFamily
                                        },
                                        maxRotation: 45
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,0,0,0.04)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 10,
                                            family: fontFamily
                                        },
                                        stepSize: 1
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                            },
                        },
                    };
                }

                if (config) {
                    this.barChart = new Chart(ctx, config);
                }
            },

            createDoughnutChart(data) {
                const ctx = document.getElementById('doughnutChart');
                if (!ctx) return;

                const fontFamily = "'Inter', system-ui, sans-serif";

                this.doughnutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: data.colors,
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 10,
                            hoverBorderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        animation: {
                            duration: 800,
                            easing: 'easeOutQuart',
                            animateRotate: true,
                            animateScale: true
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 10,
                                        family: fontFamily,
                                        weight: '500'
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 12,
                                    boxHeight: 8
                                },
                            },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleFont: {
                                    family: fontFamily,
                                    size: 12
                                },
                                bodyFont: {
                                    family: fontFamily,
                                    size: 11
                                },
                                padding: 10,
                                cornerRadius: 8,
                                boxPadding: 4
                            },
                        },
                    },
                });
            },
        }));
    </script>
    @endscript
</div>