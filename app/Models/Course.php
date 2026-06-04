<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Course extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'duration_months',
        'has_phases',
    ];

    protected $casts = [
        'has_phases' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function phases()
    {
        return $this->hasMany(CoursePhase::class);
    }
}
