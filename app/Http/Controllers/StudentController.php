<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentController extends Controller
{
    /**
     * Gerar ficha de inscrição em PDF para download
     */
    public function printFicha(Student $student)
    {
        $student->load([
            'candidate',
            'institution',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.coursePhase',
            'subjectEnrollments.subject',
        ]);
        
        $pdf = Pdf::loadView('students.ficha-inscricao-pdf', compact('student'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'Ficha_Inscricao_' . ($student->student_number ?? 'aluno') . '.pdf';
        
        return $pdf->download($filename);
    }
}
