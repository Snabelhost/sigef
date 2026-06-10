<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CardTemplateResource;
use App\Models\CardTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CardTemplatePreviewController extends Controller
{
    public function __invoke(Request $request, CardTemplate $cardTemplate): Response
    {
        $cardTemplate = $cardTemplate->fresh() ?? $cardTemplate;

        return response()->view('cards.print', [
            'template' => $cardTemplate,
            'payload' => CardTemplateResource::samplePayload($cardTemplate),
            'previewMode' => true,
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
            'showFace' => $request->input('face'),
            'printScaleMode' => $request->query('print_scale'),
            'documentName' => 'Modelo - '.$cardTemplate->name,
            'statusLabel' => 'PRE-VISUALIZACAO',
            'statusColor' => 'info',
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
