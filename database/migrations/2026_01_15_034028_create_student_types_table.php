<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->integer('order')->default(0);
            $table->boolean('has_phase')->default(false);
            $table->string('phase_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inserir tipos padrão
        DB::table('student_types')->insert([
            [
                'name' => 'Alistado',
                'description' => 'Candidato alistado, aguardando início da formação',
                'color' => 'gray',
                'order' => 1,
                'has_phase' => false,
                'phase_name' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '1ª Fase - Recruta',
                'description' => 'Aluno na primeira fase de formação',
                'color' => 'warning',
                'order' => 2,
                'has_phase' => true,
                'phase_name' => 'Fase 1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '2ª Fase - Instruendo',
                'description' => 'Aluno na segunda fase de formação',
                'color' => 'info',
                'order' => 3,
                'has_phase' => true,
                'phase_name' => 'Fase 2',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Formado - Agente',
                'description' => 'Formação concluída com sucesso',
                'color' => 'success',
                'order' => 4,
                'has_phase' => false,
                'phase_name' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Adicionar coluna student_type_id na tabela students
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('student_type_id')->nullable()->after('student_type')->constrained('student_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['student_type_id']);
            $table->dropColumn('student_type_id');
        });

        Schema::dropIfExists('student_types');
    }
};
