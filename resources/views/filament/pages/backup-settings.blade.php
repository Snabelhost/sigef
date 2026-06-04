<x-filament-panels::page>
    {{-- Schedule Config --}}
    {{ $this->schema }}

    <div class="mt-4 flex items-center gap-x-3">
        <x-filament::button wire:click="saveSchedule" icon="heroicon-o-check-circle">
            Guardar Programação
        </x-filament::button>
    </div>

    {{-- Manual Backup Section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Criar Backup Manual
        </x-slot>
        <x-slot name="description">
            Crie um backup completo da base de dados. O ficheiro SQL será guardado no servidor.
        </x-slot>

        <x-filament::button
            wire:click="createBackup"
            wire:loading.attr="disabled"
            wire:target="createBackup"
            icon="heroicon-o-arrow-down-tray">
            <span wire:loading.remove wire:target="createBackup">Criar Backup Agora</span>
            <span wire:loading wire:target="createBackup">A criar backup...</span>
        </x-filament::button>
    </x-filament::section>

    {{-- Existing Backups List --}}
    @php $backups = $this->getBackupsList(); @endphp
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Backups Existentes ({{ count($backups) }})
        </x-slot>

        @if (count($backups) > 0)
        <div class="space-y-3">
            @foreach ($backups as $index => $backup)
            <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-sm transition-all duration-150">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex-shrink-0" style="width:36px;height:36px;border-radius:8px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:13px;font-weight:700;color:rgb(59,130,246);line-height:1;">{{ $index + 1 }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $backup['filename'] }}</p>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $backup['date'] }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium" style="background:rgba(59,130,246,0.1);color:rgb(37,99,235);">{{ $backup['size'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                    <x-filament::button
                        size="sm"
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="downloadBackup('{{ $backup['filename'] }}')">
                        Descarregar
                    </x-filament::button>
                    <x-filament::button
                        size="sm"
                        color="danger"
                        icon="heroicon-o-trash"
                        wire:click="deleteBackup('{{ $backup['filename'] }}')"
                        wire:confirm="Tem a certeza que deseja eliminar este backup?">
                        Eliminar
                    </x-filament::button>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Nenhum backup encontrado</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Crie o primeiro backup clicando em "Criar Backup Agora"</p>
        </div>
        @endif
    </x-filament::section>

    {{-- Import Section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Importar Backup
        </x-slot>
        <x-slot name="description">
            Importar registos de um ficheiro SQL. Registos duplicados serão automaticamente ignorados.
        </x-slot>

        @if (!$showImportPreview)
        <div class="space-y-8">
            <div>
                <label for="importFileInput" id="dropZone"
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 16px;border:2px dashed rgb(209,213,219);border-radius:12px;cursor:pointer;background:rgb(249,250,251);transition:all 0.15s;"
                    onmouseover="this.style.borderColor='rgb(59,130,246)';this.style.background='rgba(59,130,246,0.03)'"
                    onmouseout="if(!this.classList.contains('dragging')){this.style.borderColor='rgb(209,213,219)';this.style.background='rgb(249,250,251)'}"
                    ondragover="event.preventDefault();this.classList.add('dragging');this.style.borderColor='rgb(59,130,246)';this.style.background='rgba(59,130,246,0.06)'"
                    ondragleave="this.classList.remove('dragging');this.style.borderColor='rgb(209,213,219)';this.style.background='rgb(249,250,251)'"
                    ondrop="event.preventDefault();this.classList.remove('dragging');this.style.borderColor='rgb(209,213,219)';this.style.background='rgb(249,250,251)';var f=event.dataTransfer.files[0];if(f&&f.name.endsWith('.sql')){var dt=new DataTransfer();dt.items.add(f);document.getElementById('importFileInput').files=dt.files;document.getElementById('importFileInput').dispatchEvent(new Event('change',{bubbles:true}));}">
                    <div style="font-size:32px;margin-bottom:8px;">📁</div>
                    <p style="font-size:14px;font-weight:600;color:rgb(55,65,81);">Arraste e solte ou clique para seleccionar</p>
                    <p style="font-size:12px;color:rgb(107,114,128);margin-top:4px;">Apenas ficheiros .sql são aceites</p>
                    @if($importFile)
                    <div style="margin-top:12px;padding:6px 16px;background:rgba(59,130,246,0.1);border-radius:8px;">
                        <p style="font-size:13px;font-weight:600;color:rgb(37,99,235);">{{ $importFile->getClientOriginalName() }}</p>
                    </div>
                    @endif
                </label>
                <input
                    id="importFileInput"
                    type="file"
                    wire:model="importFile"
                    accept=".sql"
                    style="display:none;" />
            </div>


            <div style="margin-top: 16px;">
                <x-filament::button
                    wire:click="previewImport"
                    wire:loading.attr="disabled"
                    wire:target="importFile,previewImport"
                    icon="heroicon-o-magnifying-glass">
                    <span wire:loading.remove wire:target="previewImport">Analisar Ficheiro</span>
                    <span wire:loading wire:target="previewImport">A analisar...</span>
                </x-filament::button>
            </div>
        </div>
        @else
        <div class="space-y-4">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $importPreview['total_records'] ?? 0 }}</p>
                    <p class="text-xs text-blue-500 mt-1">Total Registos</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $importPreview['new_records'] ?? 0 }}</p>
                    <p class="text-xs text-green-500 mt-1">Novos</p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $importPreview['existing_records'] ?? 0 }}</p>
                    <p class="text-xs text-yellow-500 mt-1">Já Existem</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $importPreview['filename'] ?? '' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $importPreview['filesize'] ?? '' }}</p>
                </div>
            </div>

            {{-- Table Details --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tabela</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Novos</th>
                            <th class="px-4 py-3 font-medium">Existentes</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($importPreview['tables'] ?? [] as $table)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs">{{ $table['table'] }}</td>
                            <td class="px-4 py-2">{{ $table['total'] }}</td>
                            <td class="px-4 py-2 text-green-600 font-medium">{{ $table['new'] }}</td>
                            <td class="px-4 py-2 text-yellow-600">{{ $table['existing'] }}</td>
                            <td class="px-4 py-2">
                                @if ($table['status'] === 'all_exist')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Todos existem</span>
                                @elseif ($table['status'] === 'skip')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Tabela ausente</span>
                                @elseif ($table['status'] === 'empty')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">Vazio</span>
                                @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Pronto</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-4 border-t dark:border-gray-700">
                @if (($importPreview['total_records'] ?? 0) > 0)
                <div class="flex items-center gap-2 mr-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="updateExisting" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Actualizar existentes</span>
                </div>

                <x-filament::button
                    wire:click="executeImport"
                    wire:loading.attr="disabled"
                    wire:target="executeImport"
                    icon="heroicon-o-arrow-up-tray">
                    <span wire:loading.remove wire:target="executeImport">
                        @if ($updateExisting)
                        Importar e Actualizar {{ $importPreview['total_records'] ?? 0 }} Registos
                        @elseif (($importPreview['new_records'] ?? 0) > 0)
                        Importar {{ $importPreview['new_records'] ?? 0 }} Novos Registos
                        @else
                        Nenhum registo novo para importar
                        @endif
                    </span>
                    <span wire:loading wire:target="executeImport">A importar...</span>
                </x-filament::button>
                @endif

                <x-filament::button wire:click="cancelImport" color="danger" icon="heroicon-o-x-mark">
                    Cancelar
                </x-filament::button>
            </div>
        </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>