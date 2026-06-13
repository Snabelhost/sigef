<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Effective extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'user_id',
        'card_template_id',
        'staff_type',
        'full_name',
        'employee_number',
        'identity_document',
        'document_type',
        'document_number',
        'gender',
        'blood_type',
        'nas',
        'country',
        'province',
        'municipality',
        'birth_date',
        'father_name',
        'mother_name',
        'position',
        'education_level',
        'situation',
        'specialization',
        'category',
        'department',
        'unit',
        'placement_organ',
        'job_function',
        'phone',
        'email',
        'hire_date',
        'photo',
        'card_issued_at',
        'work_shift',
        'work_start_time',
        'work_end_time',
        'weekly_hours',
        'work_days',
        'work_schedule_notes',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'iban',
        'swift_code',
        'salary_base',
        'salary_allowances',
        'salary_deductions',
        'salary_currency',
        'salary_notes',
        'file_identity_card',
        'file_contract',
        'file_cv',
        'file_certificate',
        'file_other_document',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'card_issued_at' => 'date',
        'work_days' => 'array',
        'salary_base' => 'decimal:2',
        'salary_allowances' => 'decimal:2',
        'salary_deductions' => 'decimal:2',
        'weekly_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cardTemplate(): BelongsTo
    {
        return $this->belongsTo(CardTemplate::class);
    }

    public static function staffTypeOptions(): array
    {
        return [
            'regime_especial' => 'Regime Especial',
            'regime_geral' => 'Regime Geral',
        ];
    }

    public static function bloodTypeOptions(): array
    {
        return [
            'A+' => 'A+',
            'A-' => 'A-',
            'B+' => 'B+',
            'B-' => 'B-',
            'AB+' => 'AB+',
            'AB-' => 'AB-',
            'O+' => 'O+',
            'O-' => 'O-',
        ];
    }

    public static function workShiftOptions(): array
    {
        return [
            'Manha' => 'Manha',
            'Tarde' => 'Tarde',
            'Noite' => 'Noite',
            'Integral' => 'Integral',
            'Pos-Laboral' => 'Pos-Laboral',
        ];
    }

    public function getIdentifierAttribute(): ?string
    {
        if ($this->staff_type === 'regime_geral') {
            return $this->nas ?: $this->identity_document;
        }

        return $this->employee_number;
    }

    public function getPositionLabelAttribute(): string
    {
        if ($this->staff_type === 'regime_geral') {
            return $this->category ?: 'Civil';
        }

        return $this->position ?: 'Efectivo';
    }
}
