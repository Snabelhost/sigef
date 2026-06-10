<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('class_students');
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_students')) {
            Schema::create('class_students', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->timestamp('enrolled_at');
                $table->timestamps();
            });
        }
    }
};
