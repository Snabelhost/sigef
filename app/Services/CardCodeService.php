<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

class CardCodeService
{
    public function qrCodeDataUri(string $value): ?string
    {
        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'scale' => 5,
                'drawLightModules' => true,
                'imageBase64' => true,
            ]);

            $rendered = (new QRCode($options))->render($value);

            return str_starts_with($rendered, 'data:image/')
                ? $rendered
                : $this->svgDataUri($rendered);
        } catch (Throwable) {
            return null;
        }
    }

    protected function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
