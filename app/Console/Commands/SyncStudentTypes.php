<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentType;
use Illuminate\Console\Command;

class SyncStudentTypes extends Command
{
    protected $signature = 'students:sync-types';
    protected $description = 'Sincroniza student_type_id com base no student_type (string) para todos os Students';

    public function handle()
    {
        $this->info('Sincronizando tipos de alunos...');
        
        $students = Student::whereNull('student_type_id')
            ->whereNotNull('student_type')
            ->get();
        
        $count = 0;
        $typesCreated = [];
        
        foreach ($students as $student) {
            $typeId = StudentType::getIdByName($student->student_type);
            $student->update(['student_type_id' => $typeId]);
            $count++;
            
            if (!in_array($student->student_type, $typesCreated)) {
                $typesCreated[] = $student->student_type;
            }
        }
        
        $this->info("✅ {$count} estudantes atualizados!");
        
        if (!empty($typesCreated)) {
            $this->info('Tipos criados/atualizados: ' . implode(', ', $typesCreated));
        }
        
        // Mostrar estatísticas
        $this->table(
            ['Tipo', 'Quantidade'],
            StudentType::withCount('students')->get()->map(fn($t) => [$t->name, $t->students_count])->toArray()
        );
        
        return Command::SUCCESS;
    }
}
