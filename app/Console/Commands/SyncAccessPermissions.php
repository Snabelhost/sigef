<?php

namespace App\Console\Commands;

use App\Support\AccessPermissionCatalog;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SyncAccessPermissions extends Command
{
    protected $signature = 'permissions:sync-catalog {--guard=web : Guard usado para criar as permissões}';

    protected $description = 'Sincroniza todas as permissões do SIGEF, incluindo painéis, páginas, recursos, relatórios, dashboard e ações.';

    public function handle(): int
    {
        $guard = (string) $this->option('guard');
        $before = Permission::query()->where('guard_name', $guard)->count();
        $created = AccessPermissionCatalog::sync($guard);
        $after = Permission::query()->where('guard_name', $guard)->count();

        $this->info("Permissões sincronizadas para o guard [{$guard}].");
        $this->line("Antes: {$before}");
        $this->line("Criadas: {$created}");
        $this->line("Total: {$after}");

        return self::SUCCESS;
    }
}
