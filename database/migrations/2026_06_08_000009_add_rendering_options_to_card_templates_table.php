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
            if (! Schema::hasColumn('card_templates', 'fallback_photo_path')) {
                $table->string('fallback_photo_path')->nullable()->after('sample_photo_path');
            }

            if (! Schema::hasColumn('card_templates', 'front_title')) {
                $table->string('front_title')->nullable()->after('brand_name');
            }

            if (! Schema::hasColumn('card_templates', 'number_label')) {
                $table->string('number_label', 50)->nullable()->after('front_title');
            }

            if (! Schema::hasColumn('card_templates', 'show_barcode')) {
                $table->boolean('show_barcode')->default(false)->after('show_qr_code');
            }
        });

        DB::table('card_templates')
            ->where('card_type', 'student')
            ->update([
                'front_title' => DB::raw("COALESCE(front_title, 'PASSE DE IDENTIFICACAO')"),
                'number_label' => DB::raw("COALESCE(number_label, 'Nº')"),
                'show_barcode' => false,
                'front_background_path' => DB::raw("COALESCE(front_background_path, 'images/cards/fundo_card.png')"),
                'back_background_path' => DB::raw("COALESCE(back_background_path, 'images/cards/fundo_card.png')"),
                'signature_image_path' => DB::raw("COALESCE(signature_image_path, 'images/cards/assinatura.png')"),
                'signature_label' => DB::raw("COALESCE(signature_label, 'O DIRECTOR NACIONAL')"),
                'signatory_name' => DB::raw("COALESCE(signatory_name, 'MANUEL GREGORIO DE SOUSA')"),
                'signatory_title' => DB::raw("COALESCE(signatory_title, '** COMISSARIO **')"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        Schema::table('card_templates', function (Blueprint $table): void {
            foreach (['show_barcode', 'number_label', 'front_title', 'fallback_photo_path'] as $column) {
                if (Schema::hasColumn('card_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
