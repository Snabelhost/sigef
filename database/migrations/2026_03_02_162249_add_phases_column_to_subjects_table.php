<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->json('phases')->nullable()->after('course_phase_id');
        });

        // Migrar dados existentes: converter course_phase_id para o novo campo phases
        $subjects = DB::table('subjects')
            ->whereNotNull('course_phase_id')
            ->get();

        foreach ($subjects as $subject) {
            $phase = DB::table('course_phases')->where('id', $subject->course_phase_id)->first();
            if ($phase) {
                $phaseName = $phase->name;
                // Normalizar nomes: "1ºFase" -> "1ª Fase", "2ºfase" -> "2ª Fase"
                if (str_contains(strtolower($phaseName), '1')) {
                    $phases = ['1ª Fase'];
                } elseif (str_contains(strtolower($phaseName), '2')) {
                    $phases = ['2ª Fase'];
                } else {
                    $phases = [$phaseName];
                }

                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update(['phases' => json_encode($phases)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('phases');
        });
    }
};
