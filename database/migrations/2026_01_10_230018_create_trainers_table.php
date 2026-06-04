<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained();
            $table->string('full_name');
            $table->string('nip')->unique();
            $table->enum('gender', ['Masculino', 'Feminino']);
            $table->foreignId('rank_id')->nullable()->constrained();
            $table->string('organ')->nullable();
            $table->string('education_level')->nullable();
            $table->string('phone')->nullable();
            $table->enum('trainer_type', ['Civil', 'Fardado']);
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
