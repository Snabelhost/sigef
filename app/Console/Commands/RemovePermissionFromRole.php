<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class RemovePermissionFromRole extends Command
{
    protected $signature = 'permissions:remove {role} {permission}';
    protected $description = 'Remove uma permissão específica de uma role';

    public function handle()
    {
        $roleName = $this->argument('role');
        $permissionName = $this->argument('permission');
        
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            $this->error("Role {$roleName} não encontrada!");
            return 1;
        }
        
        if (!$role->hasPermissionTo($permissionName)) {
            $this->warn("A role {$roleName} não tem a permissão {$permissionName}");
            return 0;
        }
        
        $role->revokePermissionTo($permissionName);
        
        $this->info("Permissão '{$permissionName}' removida da role '{$roleName}'");
        $this->info("Total de permissões da role: " . $role->permissions()->count());
        
        // Limpar cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        return 0;
    }
}
