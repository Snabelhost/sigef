<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CourseMap extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'course_id',
        'institution_id',
        'academic_year_id',
        'organ',
        'start_date',
        'end_date',
        'max_students',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getFullTitleAttribute(): string
    {
        return "{$this->course?->name} ({$this->institution?->acronym}) - {$this->academicYear?->year}";
    }
}
