<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class StudentClass extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $table = 'classes';

    protected $fillable = [
        'institution_id',
        'course_map_id',
        'course_plan_id',
        'name',
        'capacity',
        'room_number',
        'shift',
        'academic_year_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function courseMap()
    {
        return $this->belongsTo(CourseMap::class);
    }

    public function coursePlan()
    {
        return $this->belongsTo(CoursePlan::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

}
