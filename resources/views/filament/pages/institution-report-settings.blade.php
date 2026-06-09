<x-filament-panels::page>
    {{ $this->schema }}

    <div class="mt-6 flex items-center gap-x-3">
        <x-filament::button
            wire:click="save"
            icon="heroicon-o-check-circle">
            Salvar Configuração
        </x-filament::button>
    </div>
</x-filament-panels::page>
