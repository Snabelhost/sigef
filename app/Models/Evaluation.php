<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\NegativeGradeNotification;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Evaluation extends Model implements Auditable
{
    use HasFactory, AuditableTrait, SoftDeletes;

    protected $fillable = [
        'student_id',
        'institution_id',
        'subject_id',
        'course_phase_id',
        'evaluation_type',
        'score',
        'observations',
        'evaluated_by',
        'evaluator_name',
        'evaluated_at',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Evaluation $evaluation) {
            // Enviar notificação se nota for negativa (< 10) e não estiver em consola
            if ($evaluation->score < 10 && !app()->runningInConsole()) {
                $users = User::where('institution_id', $evaluation->institution_id)
                    ->where('is_active', true)
                    ->get();

                foreach ($users as $user) {
                    $user->notify(new NegativeGradeNotification($evaluation));
                }
            }
        });
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function phase()
    {
        return $this->belongsTo(CoursePhase::class, 'course_phase_id');
    }

    public function coursePhase()
    {
        return $this->phase();
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class, 'evaluated_by');
    }
}
