<x-filament-panels::page>
    {{ $this->schema }}

    <div class="mt-6 flex items-center gap-x-3">
        <x-filament::button
            wire:click="save"
            icon="heroicon-o-check-circle">
            Salvar Configurações
        </x-filament::button>

        <x-filament::button
            color="info"
            icon="heroicon-o-paper-airplane"
            wire:click="testConnection"
            wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="testConnection">Testar Email</span>
            <span wire:loading wire:target="testConnection">A enviar...</span>
        </x-filament::button>

        <x-filament::button
            color="info"
            icon="heroicon-o-device-phone-mobile"
            wire:click="testSms"
            wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="testSms">Testar SMS</span>
            <span wire:loading wire:target="testSms">A enviar...</span>
        </x-filament::button>
    </div>
</x-filament-panels::page>