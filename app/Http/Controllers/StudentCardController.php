<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Institution;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class StudentCardController extends Controller
{
    /**
     * Exibe o cartão de identificação de um estudante
     */
    public function show(Student $student)
    {
        $student->load(['candidate', 'institution', 'rank', 'provenance', 'classEnrollments.studentClass.courseMap.course']);
        
        $institution = $student->institution;
        $academicYear = AcademicYear::where('is_active', true)->first()?->name ?? date('Y');
        $courseCategory = $student->student_type ?? 'CBP';
        
        // Buscar abreviação do curso do aluno
        $courseAbbreviation = 'EPP'; // Valor padrão
        $enrollment = $student->classEnrollments->first();
        if ($enrollment && $enrollment->studentClass?->courseMap?->course) {
            $courseName = $enrollment->studentClass->courseMap->course->name;
            // Criar abreviação a partir das primeiras letras de cada palavra
            $words = explode(' ', $courseName);
            $abbreviation = '';
            foreach ($words as $word) {
                // Ignorar palavras pequenas como "de", "da", "do", "e", etc.
                if (strlen($word) > 2 && !in_array(strtolower($word), ['de', 'da', 'do', 'das', 'dos', 'para', 'com'])) {
                    $abbreviation .= strtoupper(substr($word, 0, 1));
                }
            }
            if (!empty($abbreviation)) {
                $courseAbbreviation = $abbreviation;
            }
        }

        return view('students.card', compact('student', 'institution', 'academicYear', 'courseCategory', 'courseAbbreviation'));
    }

    /**
     * Exibe cartões de múltiplos estudantes para impressão em lote
     */
    public function printBatch(Request $request)
    {
        $studentIds = $request->input('students', []);
        
        if (empty($studentIds)) {
            return back()->with('error', 'Nenhum estudante selecionado.');
        }

        $students = Student::whereIn('id', $studentIds)
            ->with(['candidate', 'institution', 'rank', 'provenance'])
            ->get();

        $academicYear = AcademicYear::where('is_active', true)->first()?->name ?? date('Y');

        return view('students.cards-batch', compact('students', 'academicYear'));
    }

    /**
     * Lista todos os estudantes com opção de gerar cartão
     */
    public function index(Request $request)
    {
        $students = Student::with(['candidate', 'institution', 'rank'])
            ->whereIn('status', ['em_formacao', 'concluiu'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('students.cards-list', compact('students'));
    }
}
