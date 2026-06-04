<div class="p-4 space-y-6">
    {{-- Resumo do utilizador --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-primary-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>
            <div class="text-right">
                @if($user->last_login_at)
                    <p class="text-sm text-gray-500 dark:text-gray-400">Último login: {{ \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">IP: {{ $user->last_login_ip ?? 'N/A' }}</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nunca fez login</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Estatísticas rápidas --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $activities->where('action', 'login')->count() }}</div>
            <div class="text-xs text-green-600 dark:text-green-400">Logins</div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $activities->where('action', 'create')->count() }}</div>
            <div class="text-xs text-blue-600 dark:text-blue-400">Criações</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg text-center">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $activities->where('action', 'update')->count() }}</div>
            <div class="text-xs text-yellow-600 dark:text-yellow-400">Edições</div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg text-center">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $activities->where('action', 'delete')->count() }}</div>
            <div class="text-xs text-red-600 dark:text-red-400">Exclusões</div>
        </div>
    </div>

    {{-- Lista de atividades --}}
    @if($activities->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>Nenhuma atividade registada para este utilizador.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ação</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Módulo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($activity->created_at)->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $actionClasses = match($activity->action) {
                                        'login' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'logout' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'create' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'update' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'delete' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'view' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                    $actionLabel = match($activity->action) {
                                        'login' => 'Login',
                                        'logout' => 'Logout',
                                        'create' => 'Criar',
                                        'update' => 'Editar',
                                        'delete' => 'Excluir',
                                        'view' => 'Ver',
                                        default => ucfirst($activity->action),
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $actionClasses }}">
                                    {{ $actionLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">
                                {{ $activity->module ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate" title="{{ $activity->description }}">
                                {{ $activity->description ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @php
                                        $deviceIcon = match($activity->device_type ?? 'desktop') {
                                            'mobile' => '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>',
                                            'tablet' => '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>',
                                            default => '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
                                        };
                                    @endphp
                                    {!! $deviceIcon !!}
                                    <div class="text-xs">
                                        <div class="text-gray-600 dark:text-gray-300">{{ $activity->browser ?? 'N/A' }}</div>
                                        <div class="text-gray-400">{{ $activity->platform ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs font-mono">
                                {{ $activity->ip_address ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="text-xs text-gray-400 dark:text-gray-500 text-center">
            Mostrando as últimas {{ $activities->count() }} atividades
        </div>
    @endif
</div>
