<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            if (! Schema::hasColumn('trainers', 'country_origin')) {
                $table->string('country_origin')->nullable()->after('gender');
            }

            if (! Schema::hasColumn('trainers', 'province')) {
                $table->string('province')->nullable()->after('country_origin');
            }

            if (! Schema::hasColumn('trainers', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('province');
            }

            if (! Schema::hasColumn('trainers', 'situation')) {
                $table->string('situation')->nullable()->after('birth_date');
            }

            if (! Schema::hasColumn('trainers', 'specialization')) {
                $table->string('specialization')->nullable()->after('education_level');
            }

            if (! Schema::hasColumn('trainers', 'job_function')) {
                $table->string('job_function')->nullable()->after('specialization');
            }

            if (! Schema::hasColumn('trainers', 'department')) {
                $table->string('department')->nullable()->after('job_function');
            }

            if (! Schema::hasColumn('trainers', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('trainers', 'father_name')) {
                $table->string('father_name')->nullable()->after('email');
            }

            if (! Schema::hasColumn('trainers', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('father_name');
            }

            if (! Schema::hasColumn('trainers', 'admission_date')) {
                $table->date('admission_date')->nullable()->after('mother_name');
            }

            if (! Schema::hasColumn('trainers', 'biography')) {
                $table->text('biography')->nullable()->after('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            foreach ([
                'country_origin',
                'province',
                'birth_date',
                'situation',
                'specialization',
                'job_function',
                'department',
                'email',
                'father_name',
                'mother_name',
                'admission_date',
                'biography',
            ] as $column) {
                if (Schema::hasColumn('trainers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
