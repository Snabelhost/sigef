<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('candidate_test_results');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('selection_tests');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasTable('selection_tests')) {
            Schema::create('selection_tests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->string('name');
                $table->string('type');
                $table->integer('order')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_documents')) {
            Schema::create('candidate_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->string('file_path');
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_test_results')) {
            Schema::create('candidate_test_results', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('selection_test_id')->constrained();
                $table->enum('result', ['Aprovado', 'Reprovado', 'Apto', 'Inapto']);
                $table->decimal('score', 8, 2)->nullable();
                $table->text('observations')->nullable();
                $table->foreignId('evaluated_by')->nullable()->constrained('users');
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();
            });
        }
    }
};
