<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransferHistory extends Model
{
    protected $fillable = [
        'student_id',
        'from_institution_id',
        'to_institution_id',
        'transferred_by',
        'student_name',
        'student_number',
        'bi_number',
        'student_type',
        'rank',
        'provenance',
        'phone',
        'course',
        'student_class',
        'cia',
        'platoon',
        'section',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'from_institution_id');
    }

    public function toInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'to_institution_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
    
    /**
     * Get student type badge color
     */
    public function getStudentTypeBadgeColor(): string
    {
        return match ($this->student_type) {
            'Recruta' => 'gray',
            'Instruendo' => 'info',
            'Formando Superior' => 'success',
            'Em Formação' => 'warning',
            default => 'primary',
        };
    }
}
