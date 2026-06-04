<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Subject extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'workload_hours',
        'course_phase_id',
        'phases',
    ];

    protected $casts = [
        'phases' => 'array',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function phase()
    {
        return $this->belongsTo(CoursePhase::class, 'course_phase_id');
    }

    public function coursePhase()
    {
        return $this->phase();
    }
}
