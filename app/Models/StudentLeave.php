<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class StudentLeave extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'student_id',
        'institution_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'approved_by',
        'status',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
