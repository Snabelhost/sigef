<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selection_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Documental, Físico, Psicotécnico, Saúde
            $table->string('type'); // dpq, comando
            $table->integer('order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selection_tests');
    }
};
