<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addUserReference('trainers', 'trainers_user_id_unique');
        $this->addUserReference('effectives', 'effectives_user_id_unique');
    }

    public function down(): void
    {
        $this->dropUserReference('effectives', 'effectives_user_id_unique');
        $this->dropUserReference('trainers', 'trainers_user_id_unique');
    }

    private function addUserReference(string $tableName, string $uniqueIndex): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'user_id')) {
            $afterColumn = Schema::hasColumn($tableName, 'institution_id')
                ? 'institution_id'
                : 'id';

            Schema::table($tableName, function (Blueprint $table) use ($afterColumn): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! $this->hasIndex($tableName, $uniqueIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($uniqueIndex): void {
                $table->unique('user_id', $uniqueIndex);
            });
        }
    }

    private function dropUserReference(string $tableName, string $uniqueIndex): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'user_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $uniqueIndex): void {
            if ($this->hasIndex($tableName, $uniqueIndex)) {
                $table->dropUnique($uniqueIndex);
            }

            $table->dropConstrainedForeignId('user_id');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
