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

        Schema::table('card_templates', function (Blueprint $table): void {
            foreach (['primary_color', 'secondary_color', 'text_color'] as $column) {
                if (Schema::hasColumn('card_templates', $column)) {
                    $table->string($column, 20)->nullable()->default(null)->change();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')
            ->whereNull('primary_color')
            ->update(['primary_color' => '#041c4f']);

        DB::table('card_templates')
            ->whereNull('secondary_color')
            ->update(['secondary_color' => '#0ea5e9']);

        DB::table('card_templates')
            ->whereNull('text_color')
            ->update(['text_color' => '#ffffff']);

        Schema::table('card_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('card_templates', 'primary_color')) {
                $table->string('primary_color', 20)->default('#041c4f')->nullable(false)->change();
            }

            if (Schema::hasColumn('card_templates', 'secondary_color')) {
                $table->string('secondary_color', 20)->default('#0ea5e9')->nullable(false)->change();
            }

            if (Schema::hasColumn('card_templates', 'text_color')) {
                $table->string('text_color', 20)->default('#ffffff')->nullable(false)->change();
            }
        });
    }
};
