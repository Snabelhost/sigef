<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Candidate extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'staff_type',
        'recruitment_type_id',
        'full_name',
        'student_number',
        'id_number',
        'gender',
        'blood_type',
        'country',
        'birth_date',
        'marital_status',
        'education_level',
        'education_area',
        'phone',
        'email',
        'father_name',
        'mother_name',
        'provenance_id',
        'current_rank_id',
        'pna_entry_date',
        'photo',
        'status',
        'academic_year_id',
        // Novos campos
        'student_type',
        'nuri',
        'cia',
        'platoon',
        'section',
        'province',
        'municipality',
        'province_id',
        'municipality_id',
        'address',
        'bilhete_identidade',
        'certificado_doc',
        'curriculum',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'pna_entry_date' => 'date',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function recruitmentType()
    {
        return $this->belongsTo(RecruitmentType::class);
    }

    public function provenance()
    {
        return $this->belongsTo(Provenance::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class, 'current_rank_id');
    }

    public function currentRank()
    {
        return $this->belongsTo(Rank::class, 'current_rank_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function municipalityRelation()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }
}
