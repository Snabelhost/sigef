<?php

namespace App\Services;

use App\Models\CardTemplate;
use App\Models\Trainer;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Throwable;

class TrainerCardService
{
    public function build(Trainer $trainer): array
    {
        $trainer->loadMissing([
            'institution',
            'rank',
            'classAssignments.subject',
            'classAssignments.studentClass',
        ]);

        $template = CardTemplate::resolveForType(CardTemplate::TYPE_PROFESSOR)
            ?? $this->fallbackTemplate();

        $documentNumber = trim((string) ($trainer->bilhete ?: $trainer->nip));
        $documentNumber = $documentNumber !== ''
            ? $documentNumber
            : str_pad((string) $trainer->getKey(), 6, '0', STR_PAD_LEFT);

        $documentLabel = $trainer->trainer_type === 'Civil' ? 'BI' : 'NIP';
        $verificationUrl = url('/admin/trainers').'?tableSearch='.rawurlencode($documentNumber);
        $institution = $trainer->institution;

        $payload = [
            'name' => $trainer->full_name ?: 'Formador',
            'initials' => $this->initials($trainer->full_name ?: 'Formador'),
            'photo_url' => $this->trainerPhotoUrl($trainer, $template),
            'logo_url' => $template->logo_url
                ?: ($institution?->logo ? asset('storage/'.$institution->logo) : asset('images/logo-policia.png')),
            'institution_name' => $institution?->name ?: ($template->brand_name ?: 'SIGEF'),
            'institution_location' => collect([$institution?->province, $institution?->municipality])->filter()->implode(' / '),
            'brand_name' => $template->brand_name ?: 'SIGEF',
            'subtitle' => $template->subtitle ?: 'Sistema Integrado de Gestao de Formacao',
            'front_title' => $template->front_title ?: 'CARTAO DO FORMADOR',
            'number_label' => $template->number_label ?: $documentLabel,
            'card_number' => $documentNumber,
            'document_label' => $documentLabel,
            'document_number' => $documentNumber,
            'regime' => $trainer->trainer_type === 'Civil' ? 'REGIME GERAL' : 'REGIME ESPECIAL',
            'rank' => $trainer->rank?->name ?: '-',
            'department' => $trainer->department ?: '-',
            'organ' => $trainer->organ ?: '-',
            'function' => $trainer->job_function ?: 'Formador',
            'discipline' => $this->mainSubject($trainer),
            'subjects' => $this->subjectNames($trainer),
            'classes' => $this->classNames($trainer),
            'phone' => $this->formatPhone($trainer->phone),
            'email' => $trainer->email ?: '-',
            'footer_text' => $template->footer_text ?: 'Este cartao identifica o portador na qualidade de professor/formador.',
            'signature_label' => $template->signature_label,
            'signatory_name' => $template->signatory_name,
            'signatory_title' => $template->signatory_title,
            'signature_url' => $template->signature_image_url,
            'qr_code_uri' => $this->qrCodeDataUri($verificationUrl),
            'verification_url' => $verificationUrl,
            'show_qr_code' => (bool) ($template->show_qr_code ?? true),
            'show_barcode' => false,
            'is_active' => (bool) $trainer->is_active,
        ];

        return [
            'trainer' => $trainer,
            'template' => $template,
            'payload' => $payload,
            'viewerId' => 'sigef-trainer-card-viewer-'.$trainer->getKey(),
        ];
    }

    protected function fallbackTemplate(): CardTemplate
    {
        return new CardTemplate([
            'card_type' => CardTemplate::TYPE_PROFESSOR,
            'name' => 'Cartao de Formador',
            'primary_color' => '#041c4f',
            'secondary_color' => '#2563eb',
            'text_color' => '#ffffff',
            'front_text_color' => '#001b4d',
            'back_text_color' => '#111827',
            'front_background_color' => '#ffffff',
            'back_background_color' => '#f8fafc',
            'front_title' => 'CARTAO DO FORMADOR',
            'number_label' => 'NIP',
            'back_title' => 'Identificacao',
            'footer_text' => 'Este cartao identifica o portador na qualidade de formador.',
            'show_qr_code' => true,
            'show_barcode' => false,
            'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
            'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
        ]);
    }

    protected function trainerPhotoUrl(Trainer $trainer, CardTemplate $template): string
    {
        $photo = trim((string) $trainer->photo);

        if ($photo !== '') {
            if (Str::startsWith($photo, ['http://', 'https://', 'data:'])) {
                return $photo;
            }

            return asset('storage/'.ltrim($photo, '/'));
        }

        if ($template->fallback_photo_url) {
            return $template->fallback_photo_url;
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($trainer->full_name ?: 'Formador').'&background=041c4f&color=fff&size=240';
    }

    protected function mainSubject(Trainer $trainer): string
    {
        return $trainer->classAssignments
            ->pluck('subject.name')
            ->filter()
            ->unique()
            ->first() ?: '-';
    }

    protected function subjectNames(Trainer $trainer): string
    {
        $subjects = $trainer->classAssignments
            ->pluck('subject.name')
            ->filter()
            ->unique()
            ->values();

        return $subjects->isEmpty() ? '-' : $subjects->implode(', ');
    }

    protected function classNames(Trainer $trainer): string
    {
        $classes = $trainer->classAssignments
            ->pluck('studentClass.name')
            ->filter()
            ->unique()
            ->values();

        return $classes->isEmpty() ? '-' : $classes->implode(', ');
    }

    protected function formatPhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '-';
        }

        return str_starts_with($phone, '+') ? $phone : '+244 '.$phone;
    }

    protected function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    protected function qrCodeDataUri(string $value): ?string
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
