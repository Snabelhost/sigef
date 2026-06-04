<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class StudentClassEnrollment extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'student_id',
        'class_id',
        'course_phase_id',
        'academic_year_id',
        'student_type',
        'classroom',
        'is_active',
        'enrolled_at',
        'enrolled_by',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function coursePhase()
    {
        return $this->belongsTo(CoursePhase::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrolledByUser()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function subjectEnrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class, 'student_id', 'student_id')
            ->where('class_id', $this->class_id)
            ->where('course_phase_id', $this->course_phase_id);
    }
}
