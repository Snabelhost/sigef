<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncSuperAdminPermissions extends Command
{
    protected $signature = 'permissions:sync-super-admin';
    protected $description = 'Sincroniza todas as permissões para a role super_admin';

    public function handle()
    {
        $role = Role::where('name', 'super_admin')->first();
        
        if (!$role) {
            $this->error('Role super_admin não encontrada!');
            return 1;
        }
        
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);
        
        $this->info("Super admin agora tem {$role->permissions()->count()} permissões (de " . Permission::count() . " total)");
        
        return 0;
    }
}
