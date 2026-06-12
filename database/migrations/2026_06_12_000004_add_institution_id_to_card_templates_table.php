<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('card_templates', 'institution_id')) {
                $table->foreignId('institution_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('institutions')
                    ->nullOnDelete();
            }

            $table->index(
                ['institution_id', 'card_type', 'card_variant', 'is_active', 'is_default'],
                'card_templates_institution_lookup_index',
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates') || ! Schema::hasColumn('card_templates', 'institution_id')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table): void {
            $table->dropIndex('card_templates_institution_lookup_index');
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};
