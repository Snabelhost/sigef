<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $actions = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'ForceDelete',
            'ForceDeleteAny',
            'Restore',
            'RestoreAny',
            'Replicate',
            'Reorder',
        ];

        $timestamp = now();
        $permissionIds = [];

        foreach (['CardTemplate', 'Effective'] as $subject) {
            foreach ($actions as $action) {
                $name = "{$action}:{$subject}";

                DB::table('permissions')->updateOrInsert(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['updated_at' => $timestamp, 'created_at' => $timestamp],
                );

                $permissionIds[] = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->value('id');
            }
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['super_admin', 'admin', 'Admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach (array_filter($permissionIds) as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', collect(['CardTemplate', 'Effective'])
                ->flatMap(fn (string $subject): array => [
                    "ViewAny:{$subject}",
                    "View:{$subject}",
                    "Create:{$subject}",
                    "Update:{$subject}",
                    "Delete:{$subject}",
                    "DeleteAny:{$subject}",
                    "ForceDelete:{$subject}",
                    "ForceDeleteAny:{$subject}",
                    "Restore:{$subject}",
                    "RestoreAny:{$subject}",
                    "Replicate:{$subject}",
                    "Reorder:{$subject}",
                ])
                ->all())
            ->delete();
    }
};
