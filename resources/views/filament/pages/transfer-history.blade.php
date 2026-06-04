<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item 
            :active="$activeTab === 'candidates'"
            wire:click="setActiveTab('candidates')"
            icon="heroicon-o-user-plus"
            :badge="$this->getCandidatesCount()"
        >
            Alistados
        </x-filament::tabs.item>

        <x-filament::tabs.item 
            :active="$activeTab === 'students'"
            wire:click="setActiveTab('students')"
            icon="heroicon-o-academic-cap"
            :badge="$this->getStudentsCount()"
        >
            Formandos
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-4">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
