<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionar institution_id na tabela candidates
        if (!Schema::hasColumn('candidates', 'institution_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });
        }
        
        // Adicionar institution_id na tabela subjects
        if (!Schema::hasColumn('subjects', 'institution_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });
        }
        
        // Adicionar institution_id na tabela selection_tests
        if (!Schema::hasColumn('selection_tests', 'institution_id')) {
            Schema::table('selection_tests', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });
        
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });
        
        Schema::table('selection_tests', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });
    }
};
