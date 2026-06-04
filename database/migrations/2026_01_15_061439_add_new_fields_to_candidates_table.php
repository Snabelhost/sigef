<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Adicionar campos que faltam
            if (!Schema::hasColumn('candidates', 'province')) {
                $table->string('province')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'municipality')) {
                $table->string('municipality')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'bilhete_identidade')) {
                $table->string('bilhete_identidade')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'certificado_doc')) {
                $table->string('certificado_doc')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'carta_conducao')) {
                $table->string('carta_conducao')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'passaporte')) {
                $table->string('passaporte')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'province')) {
                $table->renameColumn('province', 'province_id');
            }
            
            $columns = ['municipality', 'address', 'bilhete_identidade', 'certificado_doc', 'carta_conducao', 'passaporte'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('candidates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
