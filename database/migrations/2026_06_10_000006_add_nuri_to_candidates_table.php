<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidates') || Schema::hasColumn('candidates', 'nuri')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            $table->string('nuri')->nullable()->after('student_type');
            $table->index('nuri', 'idx_candidates_nuri');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('candidates') || ! Schema::hasColumn('candidates', 'nuri')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            $table->dropIndex('idx_candidates_nuri');
            $table->dropColumn('nuri');
        });
    }
};
