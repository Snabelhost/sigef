<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CardTemplate;
use App\Models\Student;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;

class StudentCardService
{
    public function build(Student $student): array
    {
        $student->loadMissing([
            'candidate',
            'candidate.academicYear',
            'institution',
            'rank',
            'provenance',
            'courseMap.academicYear',
            'classEnrollments.academicYear',
            'classEnrollments.studentClass.academicYear',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.studentClass.courseMap.academicYear',
        ]);

        $template = CardTemplate::resolveForType(CardTemplate::TYPE_STUDENT) ?? $this->fallbackTemplate();
        $institution = $student->institution;
        $enrollment = $student->classEnrollments
            ->sortByDesc(fn ($enrollment): string => ($enrollment->is_active ? '1' : '0') . '|' . ($enrollment->enrolled_at ?? $enrollment->created_at ?? ''))
            ->first();
        $studentClass = $enrollment?->studentClass;
        $academicYear = $this->studentAcademicYear($student, $enrollment);
        $course = $studentClass?->courseMap?->course;
        $courseAbbreviation = $this->abbreviateCourse($course?->name) ?: 'EPP';
        $courseCategory = $student->student_type ?: 'CBP';
        $documentLabel = $this->studentDocumentLabel($student);
        $documentNumber = $student->nuri ?: $student->student_number ?: str_pad((string) $student->id, 6, '0', STR_PAD_LEFT);
        $cardNumber = "{$student->id}/{$courseCategory}/{$courseAbbreviation}/{$academicYear}";
        $verificationUrl = route('cartoes.show', $student);

        $payload = [
            'name' => $student->candidate?->full_name ?? $student->full_name ?? 'Formando',
            'initials' => $this->initials($student->candidate?->full_name ?? 'Formando'),
            'photo_url' => $this->studentPhotoUrl($student, $template),
            'logo_url' => $template->logo_url ?: ($institution?->logo ? asset('storage/'.$institution->logo) : asset('images/logo-policia.png')),
            'institution_name' => $institution?->name ?? ($template->brand_name ?: 'SIGEF'),
            'institution_location' => collect([$institution?->province, $institution?->municipality])->filter()->implode(' / '),
            'brand_name' => $template->brand_name ?: 'SIGEF',
            'subtitle' => $template->subtitle,
            'front_title' => $template->front_title ?: 'PASSE DE IDENTIFICACAO',
            'number_label' => $template->number_label ?: 'Nº',
            'card_number' => $cardNumber,
            'academic_year' => $academicYear,
            'entity_title' => 'FORMANDO',
            'course' => $course?->name,
            'class' => $studentClass?->name,
            'document_label' => $documentLabel,
            'document_number' => $documentNumber,
            'placement' => $this->placementText($student),
            'footer_text' => $template->footer_text ?: 'Este passe identifica o portador na qualidade de formando.',
            'signature_label' => $template->signature_label,
            'signatory_name' => $template->signatory_name,
            'signatory_title' => $template->signatory_title,
            'signature_url' => $template->signature_image_url,
            'qr_code_uri' => $this->qrCodeDataUri($verificationUrl),
            'verification_url' => $verificationUrl,
            'show_qr_code' => (bool) ($template->show_qr_code ?? true),
            'show_barcode' => false,
        ];

        return [
            'student' => $student,
            'template' => $template,
            'payload' => $payload,
            'institution' => $institution,
            'academicYear' => $academicYear,
            'courseCategory' => $courseCategory,
            'courseAbbreviation' => $courseAbbreviation,
        ];
    }

    public function templatePreviewPayload(CardTemplate $template): array
    {
        $sample = $template->sample_payload ?? [];
        $academicYear = $this->formatAcademicYear(AcademicYear::query()->where('is_active', true)->first()) ?? date('Y');
        $name = $sample['name'] ?? 'Betilson Marcas';
        $documentNumber = $sample['document_number'] ?? $sample['number'] ?? 'ALN-001';

        return [
            'name' => $name,
            'initials' => $this->initials($name),
            'photo_url' => $template->sample_photo_url
                ?: $template->fallback_photo_url
                ?: 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=041c4f&color=fff&size=240',
            'logo_url' => $template->logo_url ?: asset('images/logo-policia.png'),
            'institution_name' => $template->brand_name ?: 'SIGEF',
            'institution_location' => $sample['institution_location'] ?? $template->address_line,
            'brand_name' => $template->brand_name ?: 'SIGEF',
            'subtitle' => $template->subtitle,
            'front_title' => $template->front_title ?: 'PASSE DE IDENTIFICACAO',
            'number_label' => $template->number_label ?: 'Nº',
            'card_number' => $sample['card_number'] ?? "001/CBP/EPP/{$academicYear}",
            'academic_year' => $academicYear,
            'entity_title' => $sample['entity_title'] ?? 'FORMANDO',
            'number' => $sample['number'] ?? $documentNumber,
            'course' => $sample['course'] ?? 'Curso de Ciencias Policiais',
            'class' => $sample['class'] ?? 'A/01',
            'document_label' => $sample['document_label'] ?? 'NURI',
            'document_number' => $documentNumber,
            'placement' => $sample['placement'] ?? null,
            'footer_text' => $template->footer_text ?: 'Este passe identifica o portador na qualidade de formando.',
            'signature_label' => $template->signature_label,
            'signatory_name' => $template->signatory_name,
            'signatory_title' => $template->signatory_title,
            'signature_url' => $template->signature_image_url,
            'qr_code_uri' => $this->qrCodeDataUri(url('/cartoes/preview/formando')),
            'verification_url' => url('/cartoes/preview/formando'),
            'show_qr_code' => (bool) ($template->show_qr_code ?? true),
            'show_barcode' => false,
        ];
    }

    protected function fallbackTemplate(): CardTemplate
    {
        return new CardTemplate([
            'card_type' => CardTemplate::TYPE_STUDENT,
            'name' => 'Cartao de Formando',
            'primary_color' => '#041c4f',
            'secondary_color' => '#0ea5e9',
            'text_color' => '#ffffff',
            'front_text_color' => '#001b4d',
            'back_text_color' => '#111827',
            'front_background_color' => '#ffffff',
            'back_background_color' => '#ffffff',
            'front_background_path' => 'images/cards/fundo_card.png',
            'back_background_path' => 'images/cards/fundo_card.png',
            'signature_image_path' => 'images/cards/assinatura.png',
            'front_title' => 'CARTAO DO FORMANDO',
            'number_label' => 'Nº',
            'show_qr_code' => true,
            'show_barcode' => false,
            'orientation' => CardTemplate::ORIENTATION_VERTICAL,
        ]);
    }

    protected function studentPhotoUrl(Student $student, CardTemplate $template): string
    {
        if ($student->photo) {
            return asset('storage/'.$student->photo);
        }

        if ($student->candidate?->photo) {
            return asset('storage/'.$student->candidate->photo);
        }

        if ($template->fallback_photo_url) {
            return $template->fallback_photo_url;
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($student->candidate?->full_name ?? 'F').'&background=041c4f&color=fff&size=240';
    }

    protected function studentAcademicYear(Student $student, mixed $enrollment): string
    {
        $academicYear = $enrollment?->academicYear
            ?: $enrollment?->studentClass?->academicYear
            ?: $enrollment?->studentClass?->courseMap?->academicYear
            ?: $student->courseMap?->academicYear
            ?: $student->candidate?->academicYear
            ?: AcademicYear::query()->where('is_active', true)->first();

        return $this->formatAcademicYear($academicYear) ?? date('Y');
    }

    protected function formatAcademicYear(?AcademicYear $academicYear): ?string
    {
        $year = trim((string) ($academicYear?->year ?: $academicYear?->name ?: ''));

        return $year === '' ? null : $year;
    }

    protected function studentDocumentLabel(Student $student): string
    {
        $studentType = Str::lower($student->student_type ?? '');

        return str_contains($studentType, 'formando') || str_contains($studentType, 'formacao') || str_contains($studentType, 'formação') || $studentType === 'oficial'
            ? 'NIP'
            : 'NURI';
    }

    protected function placementText(Student $student): ?string
    {
        $parts = [];

        if ($student->cia) {
            $parts[] = "{$student->cia}ª CIA";
        }

        if ($student->platoon) {
            $parts[] = "{$student->platoon}º Pel.";
        }

        if ($student->section) {
            $parts[] = "{$student->section}ª Sec.";
        }

        return $parts === [] ? null : implode(' / ', $parts);
    }

    protected function abbreviateCourse(?string $courseName): ?string
    {
        $courseName = trim((string) $courseName);

        if ($courseName === '') {
            return null;
        }

        $ignored = ['de', 'da', 'do', 'das', 'dos', 'para', 'com', 'e'];
        $letters = collect(preg_split('/\s+/', Str::ascii($courseName)) ?: [])
            ->filter(fn (string $word): bool => mb_strlen($word) > 2 && ! in_array(Str::lower($word), $ignored, true))
            ->map(fn (string $word): string => Str::upper(mb_substr($word, 0, 1)))
            ->implode('');

        return $letters ?: null;
    }

    protected function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    protected function qrCodeDataUri(string $value): string
    {
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
    }

    protected function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
