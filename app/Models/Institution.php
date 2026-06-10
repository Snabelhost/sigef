<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Institution extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'institution_type_id',
        'name',
        'acronym',
        'phone',
        'email',
        'country',
        'province',
        'municipality',
        'address',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========================================
    // Relação com o tipo de instituição
    // ========================================
    
    public function type()
    {
        return $this->belongsTo(InstitutionType::class, 'institution_type_id');
    }

    public function institutionType()
    {
        return $this->type();
    }

    // ========================================
    // Relação com utilizadores
    // ========================================
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // ========================================
    // Relações Multi-Tenancy (Painel Escola)
    // ========================================

    public function trainers()
    {
        return $this->hasMany(Trainer::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function studentClasses()
    {
        return $this->hasMany(StudentClass::class);
    }

    public function studentLeaves()
    {
        return $this->hasMany(StudentLeave::class);
    }

    public function equipmentAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    /**
     * Obter o tipo de aluno padrão para esta instituição
     * baseado no tipo de instituição (INSTITUTO/ACADEMIA → Formando, ESCOLA/CENTRO → Recruta)
     *
     * @return string|null Tipo de aluno ou null para manter o actual
     */
    public function getDefaultStudentType(): ?string
    {
        $typeName = strtoupper($this->type?->name ?? '');

        return match (true) {
            in_array($typeName, ['INSTITUTO', 'ACADEMIA']) => 'Em Formação',
            in_array($typeName, ['ESCOLA', 'CENTRO']) => '1ª Fase - Recruta',
            default => null, // Manter o tipo actual
        };
    }

    /**
     * Obter o tipo de aluno padrão para uma instituição pelo ID
     *
     * @param int $institutionId
     * @return string|null Tipo de aluno ou null para manter o actual
     */
    public static function getDefaultStudentTypeForId(int $institutionId): ?string
    {
        $institution = static::with('type')->find($institutionId);
        return $institution?->getDefaultStudentType();
    }
}
