<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncRolePermissions extends Command
{
    protected $signature = 'permissions:sync-role {role}';
    protected $description = 'Sincroniza todas as permissões para uma role específica';

    public function handle()
    {
        $roleName = $this->argument('role');
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            $this->error("Role {$roleName} não encontrada!");
            return 1;
        }
        
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);
        
        $this->info("Role '{$role->name}' agora tem {$role->permissions()->count()} permissões (de " . Permission::count() . " total)");
        
        return 0;
    }
}
