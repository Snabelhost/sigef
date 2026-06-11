<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Attendance extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'institution_id',
        'trainer_id',
        'effective_id',
        'date',
        'status',
        'entry_time',
        'exit_time',
        'period',
        'observation',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Status Labels
    public const STATUS_PRESENT = 'P';
    public const STATUS_ABSENT = 'F';
    public const STATUS_JUSTIFIED = 'J';
    public const STATUS_LATE = 'A';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PRESENT => 'Presente',
            self::STATUS_ABSENT => 'Falta',
            self::STATUS_JUSTIFIED => 'Falta Justificada',
            self::STATUS_LATE => 'Atraso',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_PRESENT => 'success',
            self::STATUS_ABSENT => 'danger',
            self::STATUS_JUSTIFIED => 'warning',
            self::STATUS_LATE => 'info',
        ];
    }

    public static function getPeriodOptions(): array
    {
        return [
            'morning' => 'Manhã',
            'afternoon' => 'Tarde',
            'full_day' => 'Dia Inteiro',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function effective()
    {
        return $this->belongsTo(Effective::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::getStatusColors()[$this->status] ?? 'gray';
    }

    public function getPeriodLabelAttribute(): string
    {
        return self::getPeriodOptions()[$this->period] ?? $this->period;
    }

    // Scopes
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForWeek($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }
}
