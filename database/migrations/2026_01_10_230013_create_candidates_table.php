<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_type_id')->constrained();
            $table->string('full_name');
            $table->string('id_number')->unique(); // Nº Bilhete
            $table->enum('gender', ['Masculino', 'Feminino']);
            $table->date('birth_date');
            $table->string('marital_status')->nullable();
            $table->string('education_level')->nullable();
            $table->string('education_area')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->foreignId('provenance_id')->nullable()->constrained();
            $table->foreignId('current_rank_id')->nullable()->constrained('ranks');
            $table->date('pna_entry_date')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, enlisted
            $table->foreignId('academic_year_id')->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
