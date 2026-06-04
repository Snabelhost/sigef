@php
    $equipamentos = \App\Models\EquipmentAssignment::where('student_id', $getRecord()->id)
        ->pluck('equipment_name')
        ->take(3)
        ->toArray();
    $total = \App\Models\EquipmentAssignment::where('student_id', $getRecord()->id)->count();
    
    // Ícones SVG customizados para equipamentos de vestuário
    $svgIcons = [
        'boot' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;"><path d="M5.5 21a.5.5 0 01-.5-.5V17h1v3h12v-3h1v3.5a.5.5 0 01-.5.5h-13zM17 16H7l-1-5h12l-1 5zm0-6H7V4.5a.5.5 0 01.5-.5h3V5h3V4h3.5a.5.5 0 01.5.5V10z"/></svg>',
        'shirt' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;"><path d="M14.664 3l2.5 3.5L21 5l-2.5 5H18v11H6V10H5.5L3 5l3.836 1.5L9.336 3h5.328zM12 5l-2 3h4l-2-3z"/></svg>',
        'pants' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;"><path d="M4 3h16v3l-3 15H7L4 6V3zm7 3v12h2V6h-2z"/></svg>',
        'cap' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;"><path d="M12 4C7 4 3 7 3 10v2h18v-2c0-3-4-6-9-6zm-9 6h18v2H3v-2zm0 4v2c0 1 2 2 9 2s9-1 9-2v-2H3z"/></svg>',
    ];
    
    // Configuração de cores e ícones para cada equipamento
    $config = [
        // Fardamento - com SVG customizado
        'Farda Camuflada' => ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'svg' => $svgIcons['shirt']],
        'Farda de Cerimónia' => ['bg' => '#ede9fe', 'text' => '#7c3aed', 'svg' => $svgIcons['shirt']],
        'Farda de Instrução' => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'svg' => $svgIcons['shirt']],
        'Calça Camuflada' => ['bg' => '#eef2ff', 'text' => '#4f46e5', 'svg' => $svgIcons['pants']],
        'Camisa Camuflada' => ['bg' => '#dbeafe', 'text' => '#2563eb', 'svg' => $svgIcons['shirt']],
        'Boina' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'svg' => $svgIcons['cap']],
        'Gorro' => ['bg' => '#ffedd5', 'text' => '#ea580c', 'svg' => $svgIcons['cap']],
        'Botas Militares' => ['bg' => '#fef3c7', 'text' => '#92400e', 'svg' => $svgIcons['boot']],
        
        // Heroicons para os demais
        'Cinto Tático' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'icon' => 'heroicon-m-arrows-pointing-out'],
        'Camisola Interior' => ['bg' => '#cffafe', 'text' => '#0891b2', 'svg' => $svgIcons['shirt']],
        'Calças Interiores' => ['bg' => '#ccfbf1', 'text' => '#0d9488', 'svg' => $svgIcons['pants']],
        'Meias' => ['bg' => '#e0e7ff', 'text' => '#4338ca', 'icon' => 'heroicon-m-arrow-down'],
        
        // Equipamento de Cama
        'Colchão' => ['bg' => '#dcfce7', 'text' => '#15803d', 'icon' => 'heroicon-m-home-modern'],
        'Travesseiro' => ['bg' => '#d1fae5', 'text' => '#059669', 'icon' => 'heroicon-m-moon'],
        'Lençol' => ['bg' => '#ecfdf5', 'text' => '#047857', 'icon' => 'heroicon-m-rectangle-stack'],
        'Cobertor' => ['bg' => '#f0fdf4', 'text' => '#166534', 'icon' => 'heroicon-m-square-3-stack-3d'],
        'Fronha' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'icon' => 'heroicon-m-square-2-stack'],
        
        // Equipamento de Higiene
        'Kit de Higiene' => ['bg' => '#cffafe', 'text' => '#0e7490', 'icon' => 'heroicon-m-beaker'],
        'Toalha' => ['bg' => '#a5f3fc', 'text' => '#0891b2', 'icon' => 'heroicon-m-sparkles'],
        'Balde' => ['bg' => '#ccfbf1', 'text' => '#0f766e', 'icon' => 'heroicon-m-archive-box'],
        
        // Equipamento Tático
        'Mochila Militar' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'icon' => 'heroicon-m-briefcase'],
        'Cantil' => ['bg' => '#ffedd5', 'text' => '#c2410c', 'icon' => 'heroicon-m-beaker'],
        'Capacete' => ['bg' => '#f3e8ff', 'text' => '#9333ea', 'icon' => 'heroicon-m-shield-check'],
        'Colete Tático' => ['bg' => '#fce7f3', 'text' => '#be185d', 'icon' => 'heroicon-m-shield-exclamation'],
        'Bastão' => ['bg' => '#fae8ff', 'text' => '#a21caf', 'icon' => 'heroicon-m-minus'],
        'Algemas' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'heroicon-m-lock-closed'],
        
        // Material Didático
        'Caderno de Apontamentos' => ['bg' => '#fef9c3', 'text' => '#a16207', 'icon' => 'heroicon-m-book-open'],
        'Manual de Instrução' => ['bg' => '#fef3c7', 'text' => '#b45309', 'icon' => 'heroicon-m-document-text'],
        'Caneta' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'icon' => 'heroicon-m-pencil'],
    ];
    
    $defaultConfig = ['bg' => '#f3f4f6', 'text' => '#4b5563', 'icon' => 'heroicon-m-cube'];
@endphp

<div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
    @if(count($equipamentos) === 0)
        <span style="color: #9ca3af; font-style: italic; font-size: 13px;">Nenhum equipamento</span>
    @else
        @foreach($equipamentos as $equip)
            @php
                $cfg = $config[$equip] ?? $defaultConfig;
            @endphp
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: {{ $cfg['bg'] }}; color: {{ $cfg['text'] }}; white-space: nowrap;">
                @if(isset($cfg['svg']))
                    {!! $cfg['svg'] !!}
                @else
                    <x-dynamic-component :component="$cfg['icon']" style="width: 14px; height: 14px; flex-shrink: 0;" />
                @endif
                {{ $equip }}
            </span>
        @endforeach
        @if($total > 3)
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #e5e7eb; color: #374151; white-space: nowrap;">
                <x-heroicon-m-ellipsis-horizontal style="width: 14px; height: 14px; flex-shrink: 0;" />
                +{{ $total - 3 }} mais
            </span>
        @endif
    @endif
</div>
