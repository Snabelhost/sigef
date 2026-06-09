<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('effectives')) {
            return;
        }

        Schema::create('effectives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('card_template_id')->nullable()->constrained('card_templates')->nullOnDelete();
            $table->string('staff_type', 40)->default('regime_especial');
            $table->string('full_name');
            $table->string('employee_number')->nullable();
            $table->string('identity_document')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->string('nas')->nullable();
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('position')->nullable();
            $table->string('category')->nullable();
            $table->string('department')->nullable();
            $table->string('unit')->nullable();
            $table->string('placement_organ')->nullable();
            $table->string('job_function')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('photo')->nullable();
            $table->date('card_issued_at')->nullable();
            $table->string('work_shift')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->decimal('weekly_hours', 6, 2)->nullable();
            $table->json('work_days')->nullable();
            $table->text('work_schedule_notes')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->decimal('salary_base', 14, 2)->nullable();
            $table->decimal('salary_allowances', 14, 2)->nullable();
            $table->decimal('salary_deductions', 14, 2)->nullable();
            $table->string('salary_currency', 10)->default('AOA');
            $table->text('salary_notes')->nullable();
            $table->string('file_identity_card')->nullable();
            $table->string('file_contract')->nullable();
            $table->string('file_cv')->nullable();
            $table->string('file_certificate')->nullable();
            $table->string('file_other_document')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('employee_number');
            $table->unique('identity_document');
            $table->index(['staff_type', 'is_active']);
            $table->index(['institution_id', 'is_active']);
            $table->index(['position', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('effectives');
    }
};
