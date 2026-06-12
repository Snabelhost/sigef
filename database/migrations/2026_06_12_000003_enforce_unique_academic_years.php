<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $indexName = 'academic_years_year_unique';

    public function up(): void
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasColumn('academic_years', 'year')) {
            return;
        }

        DB::transaction(function (): void {
            $this->normalizeAcademicYears();
            $this->mergeDuplicateAcademicYears();
        });

        if (! $this->indexExists()) {
            Schema::table('academic_years', function (Blueprint $table): void {
                $table->unique('year', $this->indexName);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_years') || ! $this->indexExists()) {
            return;
        }

        Schema::table('academic_years', function (Blueprint $table): void {
            $table->dropUnique($this->indexName);
        });
    }

    private function normalizeAcademicYears(): void
    {
        DB::table('academic_years')
            ->orderBy('id')
            ->get(['id', 'year'])
            ->each(function (object $academicYear): void {
                if ($academicYear->year === null) {
                    return;
                }

                $normalizedYear = trim((string) $academicYear->year);

                if ($normalizedYear !== (string) $academicYear->year) {
                    DB::table('academic_years')
                        ->where('id', $academicYear->id)
                        ->update(['year' => $normalizedYear]);
                }
            });
    }

    private function mergeDuplicateAcademicYears(): void
    {
        DB::table('academic_years')
            ->selectRaw('year, MIN(id) as keep_id, COUNT(*) as total')
            ->whereNotNull('year')
            ->groupBy('year')
            ->having('total', '>', 1)
            ->orderBy('keep_id')
            ->get()
            ->each(function (object $group): void {
                $duplicateIds = DB::table('academic_years')
                    ->where('year', $group->year)
                    ->where('id', '<>', $group->keep_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                if ($duplicateIds === []) {
                    return;
                }

                $this->updateAcademicYearReferences((int) $group->keep_id, $duplicateIds);

                DB::table('academic_years')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            });
    }

    private function updateAcademicYearReferences(int $keepId, array $duplicateIds): void
    {
        $this->tablesWithAcademicYearColumn()
            ->each(function (string $table) use ($keepId, $duplicateIds): void {
                DB::table($table)
                    ->whereIn('academic_year_id', $duplicateIds)
                    ->update(['academic_year_id' => $keepId]);
            });
    }

    private function tablesWithAcademicYearColumn(): Collection
    {
        return collect(DB::select(
            "SELECT TABLE_NAME AS table_name
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND COLUMN_NAME = 'academic_year_id'
               AND TABLE_NAME <> 'academic_years'"
        ))
            ->pluck('table_name')
            ->filter()
            ->unique()
            ->values();
    }

    private function indexExists(): bool
    {
        return collect(DB::select('SHOW INDEX FROM academic_years'))
            ->contains(fn (object $index): bool => (string) $index->Key_name === $this->indexName);
    }
};
