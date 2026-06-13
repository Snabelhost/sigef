<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        if (! Schema::hasColumn('card_templates', 'source_template_id')) {
            $afterColumn = Schema::hasColumn('card_templates', 'institution_id')
                ? 'institution_id'
                : 'id';

            Schema::table('card_templates', function (Blueprint $table) use ($afterColumn): void {
                $table->foreignId('source_template_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('card_templates')
                    ->nullOnDelete();
            });
        }

        if (! $this->hasIndex('card_templates', 'card_templates_source_institution_unique')) {
            Schema::table('card_templates', function (Blueprint $table): void {
                $table->unique(
                    ['source_template_id', 'institution_id'],
                    'card_templates_source_institution_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates') || ! Schema::hasColumn('card_templates', 'source_template_id')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table): void {
            if ($this->hasIndex('card_templates', 'card_templates_source_institution_unique')) {
                $table->dropUnique('card_templates_source_institution_unique');
            }

            $table->dropConstrainedForeignId('source_template_id');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
