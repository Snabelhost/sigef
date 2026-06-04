<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleObserver
{
    /**
     * Handle the Role "updated" event.
     * Limpa o cache de permissões quando uma role é atualizada.
     */
    public function updated(Role $role): void
    {
        $this->clearPermissionCache();
    }

    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->clearPermissionCache();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->clearPermissionCache();
    }

    /**
     * Handle quando as permissões são sincronizadas.
     * Este é chamado pelo evento pivot.
     */
    public function pivotAttached(Role $role, string $relationName, array $pivotIds): void
    {
        if ($relationName === 'permissions') {
            $this->clearPermissionCache();
        }
    }

    public function pivotDetached(Role $role, string $relationName, array $pivotIds): void
    {
        if ($relationName === 'permissions') {
            $this->clearPermissionCache();
        }
    }

    public function pivotUpdated(Role $role, string $relationName, array $pivotIds): void
    {
        if ($relationName === 'permissions') {
            $this->clearPermissionCache();
        }
    }

    /**
     * Limpa todos os caches relacionados a permissões.
     */
    protected function clearPermissionCache(): void
    {
        // Limpa o cache do Spatie Permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Limpa também o cache do dashboard stats (que pode ter dados relacionados)
        cache()->forget('dashboard_stats');
        
        // Log para debug (opcional - pode remover em produção)
        logger()->info('Permission cache cleared after role update');
    }
}
