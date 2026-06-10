<?php

use App\Models\CardTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        $timestamp = now();
        $types = [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF];

        DB::table('card_templates')
            ->whereIn('card_type', $types)
            ->update([
                'style' => CardTemplate::STYLE_STAFF_EFFECTIVE,
                'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                'updated_at' => $timestamp,
            ]);

        DB::table('card_templates')
            ->whereIn('card_type', $types)
            ->where(function ($query): void {
                $query->whereNull('back_title')
                    ->orWhere('back_title', '')
                    ->orWhere('back_title', 'like', 'Identificacao%');
            })
            ->update([
                'back_title' => 'PRERROGATIVA',
                'updated_at' => $timestamp,
            ]);

        DB::table('card_templates')
            ->whereIn('card_type', $types)
            ->where(function ($query): void {
                $query->whereNull('footer_text')
                    ->orWhere('footer_text', '')
                    ->orWhere('footer_text', 'like', 'Este cartao identifica%');
            })
            ->update([
                'footer_text' => 'O presente passe e intransmissivel e tem a finalidade de identificar o portador, na qualidade de {tipo} do {instituicao}.',
                'updated_at' => $timestamp,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')
            ->where('card_type', CardTemplate::TYPE_PROFESSOR)
            ->update([
                'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
                'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                'updated_at' => now(),
            ]);
    }
};
