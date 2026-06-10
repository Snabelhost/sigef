<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Observers\StudentObserver;

#[ObservedBy([StudentObserver::class])]
class Student extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'institution_id',
        'provenance_id',
        'rank_id',
        'course_map_id',
        'student_number',
        'student_type',
        'student_type_id',
        'status',
        'nuri',
        'phone',
        'cia',
        'platoon',
        'section',
        'current_phase_id',
        'enrollment_date',
        'conclusion_date',
        'photo',
        'bilhete_identidade',
        'certificado_doc',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'conclusion_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function provenance()
    {
        return $this->belongsTo(Provenance::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function courseMap()
    {
        return $this->belongsTo(CourseMap::class);
    }

    public function currentPhase()
    {
        return $this->belongsTo(CoursePhase::class, 'current_phase_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function leaves()
    {
        return $this->hasMany(StudentLeave::class);
    }

    public function classEnrollments()
    {
        return $this->hasMany(StudentClassEnrollment::class);
    }

    public function subjectEnrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function equipmentAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function getFullNameAttribute()
    {
        // Forçar carregamento da relação se não estiver carregada
        if (!$this->relationLoaded('candidate')) {
            $this->load('candidate');
        }

        // Tentar via relação
        if ($this->candidate && $this->candidate->full_name) {
            return $this->candidate->full_name;
        }

        // Fallback: buscar diretamente na BD
        if ($this->candidate_id) {
            $candidate = Candidate::find($this->candidate_id);
            if ($candidate) {
                return $candidate->full_name;
            }
        }

        return '-';
    }

    public function studentTypeRelation()
    {
        return $this->belongsTo(StudentType::class, 'student_type_id');
    }

    public function getStudentTypeLabel()
    {
        // Se tiver relação com StudentType, usa o nome da tabela
        if ($this->studentTypeRelation) {
            return $this->studentTypeRelation->name;
        }

        // Fallback para compatibilidade
        return match ($this->student_type) {
            'Alistado' => 'Alistado',
            'Recruta' => '1ª Fase - Recruta',
            'Instruendo' => '2ª Fase - Instruendo',
            'Agente' => 'Formado - Agente',
            default => $this->student_type,
        };
    }

    public function getStudentTypeColor()
    {
        if ($this->studentTypeRelation) {
            return $this->studentTypeRelation->color;
        }

        return match ($this->student_type) {
            'Alistado' => 'gray',
            'Recruta' => 'warning',
            'Instruendo' => 'info',
            'Agente' => 'success',
            default => 'gray',
        };
    }
}
