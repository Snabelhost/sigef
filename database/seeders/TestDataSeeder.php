<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Candidate;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\Course;
use App\Models\Subject;
use App\Models\CourseMap;
use App\Models\CoursePlan;
use App\Models\CoursePhase;
use App\Models\StudentClass;
use App\Models\Evaluation;
use App\Models\StudentLeave;
use App\Models\AcademicYear;
use App\Models\Provenance;
use App\Models\Rank;
use App\Models\RecruitmentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('A criar dados de teste para o SIGEF...');

        // 1. Criar Tipos de Instituição se não existirem
        $tipoEscola = InstitutionType::firstOrCreate(
            ['name' => 'Escola de Formação'],
            ['description' => 'Estabelecimentos de ensino policial']
        );

        // 2. Criar Instituições (Escolas)
        $escolas = [
            ['name' => 'Escola de Formação de Quadros de Polícia - Luanda', 'acronym' => 'EFQP-LDA', 'province' => 'Luanda'],
            ['name' => 'Centro de Instrução Policial de Benguela', 'acronym' => 'CIP-BGL', 'province' => 'Benguela'],
            ['name' => 'Escola de Formação de Polícia do Huambo', 'acronym' => 'EFP-HMB', 'province' => 'Huambo'],
        ];

        $institutions = [];
        foreach ($escolas as $escola) {
            $institutions[] = Institution::firstOrCreate(
                ['acronym' => $escola['acronym']],
                [
                    'name' => $escola['name'],
                    'institution_type_id' => $tipoEscola->id,
                    'province' => $escola['province'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Criar Cursos
        $cursos = [
            ['name' => 'Curso de Formação de Agentes', 'duration_months' => 9, 'has_phases' => true],
            ['name' => 'Curso de Promoção a Subchefe', 'duration_months' => 6, 'has_phases' => true],
            ['name' => 'Curso de Oficiais', 'duration_months' => 24, 'has_phases' => true],
        ];

        $courses = [];
        foreach ($cursos as $curso) {
            $courses[] = Course::firstOrCreate(['name' => $curso['name']], $curso);
        }

        // 4. Criar Disciplinas
        $disciplinas = [
            'Direito Penal', 'Direito Processual Penal', 'Técnica de Investigação Criminal',
            'Ordem Unida', 'Educação Física', 'Informática Básica', 'Armamento e Tiro',
            'Primeiros Socorros', 'Ética e Deontologia Profissional', 'Português',
        ];

        $subjects = [];
        foreach ($disciplinas as $disciplina) {
            $subjects[] = Subject::firstOrCreate(['name' => $disciplina]);
        }

        // 5. Criar Plano de Curso e Fases
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::first();
        }

        foreach ($courses as $course) {
            $plan = CoursePlan::firstOrCreate(
                ['course_id' => $course->id, 'academic_year_id' => $academicYear->id],
                ['is_active' => true]
            );

            // Criar fases
            if ($course->has_phases) {
                for ($i = 1; $i <= 3; $i++) {
                    CoursePhase::firstOrCreate(
                        ['course_id' => $course->id, 'order' => $i],
                        ['name' => "Fase $i"]
                    );
                }
            }

            // Criar mapa de curso para cada escola
            foreach ($institutions as $institution) {
                CourseMap::firstOrCreate(
                    ['course_id' => $course->id, 'academic_year_id' => $academicYear->id, 'institution_id' => $institution->id],
                    ['is_active' => true, 'max_students' => 100]
                );
            }
        }

        // 6. Criar Formadores
        $patentes = Rank::all();
        $nomes = [
            ['full_name' => 'Carlos Manuel da Silva', 'gender' => 'Masculino'],
            ['full_name' => 'Maria Fernanda Lopes', 'gender' => 'Feminino'],
            ['full_name' => 'João Pedro Gonçalves', 'gender' => 'Masculino'],
            ['full_name' => 'Ana Beatriz Mendes', 'gender' => 'Feminino'],
            ['full_name' => 'António José Ferreira', 'gender' => 'Masculino'],
            ['full_name' => 'Helena Cristina Santos', 'gender' => 'Feminino'],
        ];

        $trainers = [];
        $nipCounter = 1000;
        foreach ($institutions as $institution) {
            foreach (array_slice($nomes, 0, 2) as $nome) {
                $trainers[] = Trainer::firstOrCreate(
                    ['nip' => 'NIP' . str_pad($nipCounter++, 6, '0', STR_PAD_LEFT)],
                    [
                        'institution_id' => $institution->id,
                        'full_name' => $nome['full_name'],
                        'gender' => $nome['gender'],
                        'rank_id' => $patentes->random()->id,
                        'trainer_type' => 'Fardado',
                        'is_active' => true,
                    ]
                );
            }
        }

        // 7. Criar Candidatos
        $provincias = Provenance::all();
        $tiposRecrutamento = RecruitmentType::all();
        
        $nomesMasculinos = [
            'Pedro', 'Manuel', 'José', 'António', 'Francisco', 'João', 'Carlos', 'Paulo',
            'Miguel', 'Fernando', 'Ricardo', 'David', 'André', 'Bruno', 'Tiago', 'Rui',
        ];
        $nomesFemininos = [
            'Maria', 'Ana', 'Helena', 'Rosa', 'Marta', 'Catarina', 'Isabel', 'Teresa',
            'Paula', 'Sandra', 'Carla', 'Sofia', 'Joana', 'Sara', 'Diana', 'Raquel',
        ];
        $apelidos = [
            'Silva', 'Santos', 'Ferreira', 'Pereira', 'Oliveira', 'Costa', 'Rodrigues',
            'Martins', 'Jesus', 'Sousa', 'Fernandes', 'Gonçalves', 'Gomes', 'Lopes',
        ];

        $candidates = [];
        $biCounter = 1000000000;
        
        for ($i = 0; $i < 100; $i++) {
            $isMale = rand(0, 1);
            $firstName = $isMale ? $nomesMasculinos[array_rand($nomesMasculinos)] : $nomesFemininos[array_rand($nomesFemininos)];
            $lastName1 = $apelidos[array_rand($apelidos)];
            $lastName2 = $apelidos[array_rand($apelidos)];
            $fullName = "$firstName $lastName1 $lastName2";
            
            $status = ['pending', 'approved', 'rejected', 'admitted'][rand(0, 3)];
            
            $candidates[] = Candidate::firstOrCreate(
                ['id_number' => str_pad($biCounter++, 14, '0', STR_PAD_LEFT) . 'LA' . str_pad(rand(1, 99), 3, '0', STR_PAD_LEFT)],
                [
                    'recruitment_type_id' => $tiposRecrutamento->random()->id,
                    'full_name' => $fullName,
                    'gender' => $isMale ? 'Masculino' : 'Feminino',
                    'birth_date' => now()->subYears(rand(18, 30))->subDays(rand(0, 365)),
                    'marital_status' => ['solteiro', 'casado'][rand(0, 1)],
                    'education_level' => ['12ª Classe', 'Licenciatura', 'Bacharelato'][rand(0, 2)],
                    'phone' => '9' . rand(10000000, 99999999),
                    'provenance_id' => $provincias->random()->id,
                    'status' => $status,
                    'academic_year_id' => $academicYear->id,
                ]
            );
        }

        // 8. Criar Formandos (apenas candidatos admitidos)
        $admittedCandidates = Candidate::where('status', 'admitted')->get();
        $courseMaps = CourseMap::all();
        $phases = CoursePhase::all();
        
        $students = [];
        $orderCounter = 1;
        
        foreach ($admittedCandidates as $candidate) {
            $courseMap = $courseMaps->random();
            $institution = Institution::find($courseMap->institution_id);
            
            $students[] = Student::firstOrCreate(
                ['candidate_id' => $candidate->id],
                [
                    'institution_id' => $courseMap->institution_id,
                    'course_map_id' => $courseMap->id,
                    'student_number' => str_pad($orderCounter++, 4, '0', STR_PAD_LEFT),
                    'student_type' => ['cadete', 'praça'][rand(0, 1)],
                    'status' => ['alistado', 'frequenta', 'frequenta', 'frequenta'][rand(0, 3)],
                    'nuri' => 'NURI' . rand(100000, 999999),
                    'cia' => 'CIA-' . chr(rand(65, 67)),
                    'platoon' => 'P' . rand(1, 4),
                    'section' => 'S' . rand(1, 3),
                    'current_phase_id' => $phases->isNotEmpty() ? $phases->random()->id : null,
                    'enrollment_date' => now()->subMonths(rand(1, 6)),
                ]
            );
        }

        // 9. Criar Turmas
        foreach ($institutions as $institution) {
            $courseMap = CourseMap::where('institution_id', $institution->id)->first();
            if ($courseMap) {
                StudentClass::firstOrCreate(
                    ['name' => 'Turma A - ' . $institution->acronym, 'institution_id' => $institution->id],
                    [
                        'course_map_id' => $courseMap->id,
                        'academic_year_id' => $academicYear->id,
                    ]
                );
            }
        }

        // 10. Criar Avaliações - Precisamos de utilizadores
        $studentsWithStatus = Student::where('status', 'frequenta')->get();
        
        // Criar um utilizador avaliador por escola
        $evaluatorUsers = [];
        foreach ($institutions as $institution) {
            $user = User::firstOrCreate(
                ['email' => 'avaliador.' . strtolower($institution->acronym) . '@sigef.ao'],
                [
                    'name' => 'Avaliador ' . $institution->acronym,
                    'password' => bcrypt('password'),
                    'institution_id' => $institution->id,
                    'is_active' => true,
                ]
            );
            $evaluatorUsers[$institution->id] = $user;
        }
        
        foreach ($studentsWithStatus as $student) {
            // 3-5 avaliações por formando
            for ($i = 0; $i < rand(3, 5); $i++) {
                $subject = $subjects[array_rand($subjects)];
                $evaluator = $evaluatorUsers[$student->institution_id] ?? null;
                
                if ($evaluator) {
                    Evaluation::withoutEvents(fn() => Evaluation::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'subject_id' => $subject->id,
                            'evaluation_type' => ['frequencia', 'exame', 'pratico'][rand(0, 2)],
                        ],
                        [
                            'institution_id' => $student->institution_id,
                            'score' => rand(5, 20),
                            'evaluated_by' => $evaluator->id,
                            'evaluated_at' => now()->subDays(rand(1, 60)),
                        ]
                    ));
                }
            }
        }

        // 11. Criar algumas Dispensas
        $studentsForLeave = $studentsWithStatus->take(10);
        
        foreach ($studentsForLeave as $student) {
            StudentLeave::withoutEvents(fn() => StudentLeave::firstOrCreate(
                ['student_id' => $student->id, 'start_date' => now()->subDays(rand(5, 30))],
                [
                    'institution_id' => $student->institution_id,
                    'leave_type' => ['saude', 'pessoal', 'servico'][rand(0, 2)],
                    'end_date' => now()->addDays(rand(1, 10)),
                    'reason' => 'Motivo de teste gerado automaticamente.',
                    'status' => ['pending', 'approved'][rand(0, 1)],
                ]
            ));
        }

        $this->command->info('✅ Dados de teste criados com sucesso!');
        $this->command->info("   - {$institutions[0]->name} e mais " . (count($institutions) - 1) . " escolas");
        $this->command->info("   - " . count($candidates) . " candidatos");
        $this->command->info("   - " . count($students) . " formandos");
        $this->command->info("   - " . Evaluation::count() . " avaliações");
    }
}
