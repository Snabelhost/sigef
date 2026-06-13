<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Services\TrainerCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrainerCardPreviewController extends Controller
{
    public function __invoke(Request $request, Trainer $trainer): Response
    {
        $institutionId = (int) $request->query('institution_id');
        $data = app(TrainerCardService::class)->build($trainer, $institutionId > 0 ? $institutionId : null);

        return response()->view('cards.print', $data + [
            'previewMode' => true,
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
            'showFace' => $request->input('face'),
            'printScaleMode' => $request->query('print_scale'),
            'documentName' => 'Formadores - '.($trainer->full_name ?: 'Formador'),
            'entityLabel' => 'Formadores',
            'statusLabel' => $trainer->is_active ? 'ACTIVO' : 'INACTIVO',
            'statusColor' => $trainer->is_active ? 'success' : 'danger',
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
