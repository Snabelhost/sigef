<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\StudentCardService;
use Illuminate\Http\Request;

class StudentCardController extends Controller
{
    public function show(Student $student, StudentCardService $studentCardService)
    {
        return view('students.card', $studentCardService->build($student));
    }

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

    public function index(Request $request)
    {
        $students = Student::with(['candidate', 'institution', 'rank'])
            ->whereIn('status', ['em_formacao', 'concluiu'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('students.cards-list', compact('students'));
    }
}
