<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates') || ! Schema::hasColumn('card_templates', 'number_label')) {
            return;
        }

        DB::table('card_templates')
            ->where(function ($query): void {
                $query->where('number_label', 'like', '%MECANOGRAF%')
                    ->orWhere('number_label', 'like', '%MECANOGR%')
                    ->orWhere('number_label', 'like', '%MECANOG%');
            })
            ->update([
                'number_label' => 'NIP/NURI',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
