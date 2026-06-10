<?php

namespace App\Http\Controllers;

use App\Filament\Resources\EffectiveResource;
use App\Models\Effective;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EffectiveCardPreviewController extends Controller
{
    public function __invoke(Request $request, Effective $effective): Response
    {
        $effective->loadMissing(['institution', 'cardTemplate']);

        $template = EffectiveResource::cardTemplateForRecord($effective);
        $payload = EffectiveResource::cardPayload($effective, $template);

        return response()->view('cards.print', [
            'template' => $template,
            'payload' => $payload,
            'previewMode' => true,
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
            'showFace' => $request->input('face'),
            'printScaleMode' => $request->query('print_scale'),
            'documentName' => 'Efectivos - '.($payload['name'] ?? 'Efectivo'),
            'entityLabel' => 'Efectivos',
            'statusLabel' => $effective->is_active ? 'ACTIVO' : 'INACTIVO',
            'statusColor' => $effective->is_active ? 'success' : 'danger',
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
