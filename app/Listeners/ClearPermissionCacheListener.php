<?php

namespace App\Listeners;

use Spatie\Permission\PermissionRegistrar;

class ClearPermissionCacheListener
{
    /**
     * Limpa o cache de permissões do Spatie.
     * Este listener pode ser conectado a qualquer evento que modifique permissões.
     */
    public function handle($event = null): void
    {
        // Limpa o cache de permissões do Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Limpa caches relacionados do dashboard
        cache()->forget('dashboard_stats');
        cache()->forget('candidates_by_province_chart');
        
        logger()->info('Permission cache cleared by listener');
    }
}
