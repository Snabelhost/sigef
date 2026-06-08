<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_class_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('trainer_class_assignments', 'frequency_year')) {
                $table->string('frequency_year')->nullable()->after('academic_year_id');
            }

            if (! Schema::hasColumn('trainer_class_assignments', 'shift')) {
                $table->string('shift')->nullable()->after('frequency_year');
            }

            if (! Schema::hasColumn('trainer_class_assignments', 'day_of_week')) {
                $table->string('day_of_week')->nullable()->after('shift');
            }

            if (! Schema::hasColumn('trainer_class_assignments', 'start_time')) {
                $table->time('start_time')->nullable()->after('day_of_week');
            }

            if (! Schema::hasColumn('trainer_class_assignments', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (! Schema::hasColumn('trainer_class_assignments', 'lesson_type')) {
                $table->string('lesson_type')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trainer_class_assignments', function (Blueprint $table) {
            foreach ([
                'frequency_year',
                'shift',
                'day_of_week',
                'start_time',
                'end_time',
                'lesson_type',
            ] as $column) {
                if (Schema::hasColumn('trainer_class_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
