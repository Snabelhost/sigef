<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentCardPreviewController extends Controller
{
    public function __invoke(Request $request, Student $student, StudentCardService $studentCardService): Response
    {
        $data = $studentCardService->build($student);

        return response()->view('cards.print', $data + [
            'previewMode' => true,
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
            'showFace' => $request->input('face'),
            'printScaleMode' => $request->query('print_scale'),
            'documentName' => 'Formandos - '.($data['payload']['name'] ?? 'Formando'),
            'entityLabel' => 'Formandos',
            'statusLabel' => $student->status ?: ($student->student_type ?: 'ACTIVO'),
            'statusColor' => 'success',
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
