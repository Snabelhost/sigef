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
            if (! Schema::hasColumn('card_templates', 'header_text_color')) {
                $table->string('header_text_color', 20)->nullable()->after('front_text_color');
            }
        });

        if (Schema::hasColumn('card_templates', 'header_text_color')) {
            DB::table('card_templates')
                ->whereNull('header_text_color')
                ->update([
                    'header_text_color' => DB::raw("COALESCE(front_text_color, text_color, '#061b42')"),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('card_templates', 'header_text_color')) {
                $table->dropColumn('header_text_color');
            }
        });
    }
};
