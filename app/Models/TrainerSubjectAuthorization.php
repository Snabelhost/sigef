<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerSubjectAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'institution_id',
        'subject_id',
        'course_id',
        'authorized_by',
    ];

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Verifica se já existe outro formador com a mesma combinação de instituição, curso e disciplina
     */
    public static function hasAnotherTrainer(
        int $institutionId,
        int $courseId,
        int $subjectId,
        ?int $excludeTrainerId = null
    ): bool {
        $query = static::where('institution_id', $institutionId)
            ->where('course_id', $courseId)
            ->where('subject_id', $subjectId);
        
        if ($excludeTrainerId) {
            $query->where('trainer_id', '!=', $excludeTrainerId);
        }
        
        return $query->exists();
    }

    /**
     * Retorna o formador que já tem esta combinação (se existir)
     */
    public static function getExistingTrainer(
        int $institutionId,
        int $courseId,
        int $subjectId,
        ?int $excludeTrainerId = null
    ): ?Trainer {
        $query = static::where('institution_id', $institutionId)
            ->where('course_id', $courseId)
            ->where('subject_id', $subjectId);
        
        if ($excludeTrainerId) {
            $query->where('trainer_id', '!=', $excludeTrainerId);
        }
        
        $auth = $query->first();
        return $auth?->trainer;
    }

    /**
     * Retorna todas as disciplinas com múltiplos formadores (duplicados)
     */
    public static function getDuplicates(): \Illuminate\Support\Collection
    {
        return static::select('institution_id', 'course_id', 'subject_id')
            ->selectRaw('COUNT(DISTINCT trainer_id) as trainer_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT trainer_id) as trainer_ids')
            ->groupBy('institution_id', 'course_id', 'subject_id')
            ->havingRaw('COUNT(DISTINCT trainer_id) > 1')
            ->with(['institution', 'course', 'subject'])
            ->get();
    }
}
