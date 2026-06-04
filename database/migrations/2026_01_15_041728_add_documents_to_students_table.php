<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('section');
            $table->string('bilhete_identidade')->nullable()->after('photo');
            $table->string('certificado_doc')->nullable()->after('bilhete_identidade');
            $table->string('carta_conducao')->nullable()->after('certificado_doc');
            $table->string('passaporte')->nullable()->after('carta_conducao');
            $table->foreignId('provenance_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
            $table->foreignId('rank_id')->nullable()->after('provenance_id')->constrained('ranks')->nullOnDelete();
            $table->date('conclusion_date')->nullable()->after('enrollment_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['provenance_id']);
            $table->dropForeign(['rank_id']);
            $table->dropColumn([
                'photo',
                'bilhete_identidade',
                'certificado_doc',
                'carta_conducao',
                'passaporte',
                'provenance_id',
                'rank_id',
                'conclusion_date'
            ]);
        });
    }
};
