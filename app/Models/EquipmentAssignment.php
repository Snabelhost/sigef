<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'institution_id',
        'equipment_name',
        'quantity',
        'assigned_at',
        'returned_at',
        'condition',
        'assigned_by',
        'assigned_by_name',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
