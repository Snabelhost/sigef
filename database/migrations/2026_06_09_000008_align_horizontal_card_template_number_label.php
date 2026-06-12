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

        DB::table('card_templates')
            ->whereIn('card_type', [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF])
            ->where(function ($query): void {
                $query->whereNull('number_label')
                    ->orWhere('number_label', '')
                    ->orWhere('number_label', 'NIP')
                    ->orWhere('number_label', 'NIP/BI')
                    ->orWhere('number_label', 'BI');
            })
            ->update([
                'number_label' => 'NIP/NURI',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')
            ->whereIn('card_type', [CardTemplate::TYPE_PROFESSOR, CardTemplate::TYPE_STAFF])
            ->where('number_label', 'NIP/NURI')
            ->update([
                'number_label' => null,
                'updated_at' => now(),
            ]);
    }
};
