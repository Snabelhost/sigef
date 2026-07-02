<?php

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

        DB::table('card_templates')
            ->whereIn('name', ['Cartao de Professor', 'Cartão de Professor'])
            ->update(['name' => 'Cartão de Formador']);

        DB::table('card_templates')
            ->where('footer_text', 'Este cartao identifica o portador na qualidade de professor/formador.')
            ->update(['footer_text' => 'Este cartao identifica o portador na qualidade de formador.']);
    }

    public function down(): void
    {
        // Terminology updates are intentionally not reverted in user-facing data.
    }
};
