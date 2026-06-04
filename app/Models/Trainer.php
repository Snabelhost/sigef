<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Trainer extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'full_name',
        'nip',
        'bilhete',
        'gender',
        'rank_id',
        'organ',
        'education_level',
        'phone',
        'trainer_type',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    // Relação many-to-many com instituições
    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'trainer_institution')->withTimestamps();
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function subjectAuthorizations()
    {
        return $this->hasMany(TrainerSubjectAuthorization::class);
    }

    public function classAssignments()
    {
        return $this->hasMany(TrainerClassAssignment::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'trainer_class_assignments');
    }

    public function classes()
    {
        return $this->belongsToMany(StudentClass::class, 'trainer_class_assignments', 'trainer_id', 'class_id');
    }
}
