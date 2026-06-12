<?php

namespace App\Console\Commands;

use App\Support\AccessPermissionCatalog;
use App\Support\DefaultRolePermissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncDefaultRoleProfiles extends Command
{
    protected $signature = 'permissions:sync-default-profiles {--guard=web : Guard usado para criar os perfis}';

    protected $description = 'Cria e sincroniza os perfis padrao do SIGEF com as permissoes corretas por painel.';

    public function handle(): int
    {
        $guard = (string) $this->option('guard');
        $fullAccessRoles = ['super_admin', 'admin', 'admin_geral'];

        AccessPermissionCatalog::sync($guard);

        $availablePermissions = Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->all();

        foreach (DefaultRolePermissions::profiles() as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            $validPermissions = in_array($roleName, $fullAccessRoles, true)
                ? $availablePermissions
                : collect($permissions)
                    ->intersect($availablePermissions)
                    ->values()
                    ->all();

            $role->syncPermissions($validPermissions);

            $this->line("{$roleName}: " . count($validPermissions) . ' permissoes');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Perfis padrao sincronizados com sucesso.');

        return self::SUCCESS;
    }
}
