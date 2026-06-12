<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PANEL_PERMISSIONS = [
        'AccessPanel:Admin' => ['admin', 'panel_user', 'admin_admin'],
        'AccessPanel:Escola' => ['escola_admin', 'escola_user'],
        'AccessPanel:Professores' => ['professores_admin', 'professores_user'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        foreach (self::PANEL_PERMISSIONS as $permissionName => $roleNames) {
            $permissionId = $this->permissionId($permissionName);

            foreach ($roleNames as $roleName) {
                $roleId = DB::table('roles')
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (! $roleId) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PANEL_PERMISSIONS))
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionId(string $name): int
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => $name, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return (int) DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');
    }
};
