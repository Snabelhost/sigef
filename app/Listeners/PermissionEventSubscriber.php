<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
use Spatie\Permission\PermissionRegistrar;

class PermissionEventSubscriber
{
    /**
     * Limpa o cache de permissões quando qualquer evento de permissão ocorre.
     */
    public function handlePermissionEvent($event): void
    {
        // Limpa o cache do Spatie Permission imediatamente
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Limpa também a chave direta do cache
        cache()->forget('spatie.permission.cache');
        
        // Limpa caches relacionados do dashboard
        cache()->forget('dashboard_stats');
        
        logger()->info('Permission cache cleared by event', [
            'event' => get_class($event),
        ]);
    }

    /**
     * Registra os listeners para os eventos.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            // Eventos de Role
            \Spatie\Permission\Events\RoleAttached::class => 'handlePermissionEvent',
            \Spatie\Permission\Events\RoleDetached::class => 'handlePermissionEvent',
            
            // Eventos de Permission
            \Spatie\Permission\Events\PermissionAttached::class => 'handlePermissionEvent',
            \Spatie\Permission\Events\PermissionDetached::class => 'handlePermissionEvent',
        ];
    }
}
