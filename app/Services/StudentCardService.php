<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CardTemplate;
use App\Models\Institution;
use App\Models\Student;
use App\Support\PublicStorage;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;

class StudentCardService
{
    public function build(Student $student, ?int $institutionId = null): array
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
            'classEnrollments.studentClass.institution',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.studentClass.courseMap.academicYear',
        ]);

        $enrollment = $student->classEnrollments
            ->sortByDesc(fn ($enrollment): string => ($enrollment->is_active ? '1' : '0') . '|' . ($enrollment->enrolled_at ?? $enrollment->created_at ?? ''))
            ->first();
        $studentClass = $enrollment?->studentClass;
        $institution = $this->contextInstitution($student, $studentClass, $institutionId);
        $template = CardTemplate::resolveForType(CardTemplate::TYPE_STUDENT, null, $institution?->id) ?? $this->fallbackTemplate();
        $academicYear = $this->studentAcademicYear($student, $enrollment);
        $course = $studentClass?->courseMap?->course;
        $courseAbbreviation = $this->abbreviateCourse($course?->name) ?: 'EPP';
        $courseCategory = $student->student_type ?: 'CBP';
        $documentLabel = $this->studentDocumentLabel($student);
        $documentNumber = $student->nuri ?: $student->student_number ?: str_pad((string) $student->id, 6, '0', STR_PAD_LEFT);
        $cardNumber = "{$student->id}/{$courseCategory}/{$courseAbbreviation}/{$academicYear}";
        $verificationUrl = route('cartoes.show', $student);
        $templateBrandName = trim((string) $template->brand_name);
        $templateSubtitle = trim((string) $template->subtitle);
        $templateAddress = trim((string) $template->address_line);
        $institutionName = trim((string) $institution?->name);
        $institutionAcronym = trim((string) $institution?->acronym);
        $headerName = $templateBrandName !== '' ? $templateBrandName : ($institutionName !== '' ? $institutionName : 'SIGEF');
        $headerSubtitle = $templateSubtitle !== '' ? $templateSubtitle : $institutionAcronym;
        $institutionLocation = $templateAddress !== ''
            ? $templateAddress
            : collect([$institution?->province, $institution?->municipality])->filter()->implode(' / ');
        $entityTitle = $this->studentCardEntityTitle($student);

        $payload = [
            'name' => $student->candidate?->full_name ?? $student->full_name ?? 'Formando',
            'initials' => $this->initials($student->candidate?->full_name ?? 'Formando'),
            'photo_url' => $this->studentPhotoUrl($student, $template),
            'logo_url' => $template->logo_url ?: (PublicStorage::url($institution?->logo, requireExisting: true) ?: asset('images/logo-policia.png')),
            'institution_name' => $headerName,
            'institution_location' => $institutionLocation,
            'brand_name' => $headerName,
            'subtitle' => $headerSubtitle,
            'front_title' => $template->front_title ?: 'PASSE DE ACESSO',
            'number_label' => $template->number_label ?: 'Nº',
            'card_number' => $cardNumber,
            'academic_year' => $academicYear,
            'access_until' => $this->defaultAccessUntil(),
            'entity_title' => $entityTitle,
            'course' => $course?->name,
            'class' => $studentClass?->name,
            'cia' => $this->formatCia($student->cia),
            'platoon' => $this->formatPlatoon($student->platoon),
            'section' => $this->formatSection($student->section),
            'document_label' => $documentLabel,
            'document_number' => $documentNumber,
            'placement' => $this->placementText($student),
            'footer_text' => $template->footer_text ?: 'Este passe tem finalidade identificar o portador, permitir o seu acesso e permanencia a instituicao, na qualidade de '.$entityTitle.' do SIGEF ate {access_until}',
            'signature_label' => $template->signature_label ?: 'O DIRECTOR',
            'signatory_name' => $template->signatory_name ?: 'DESTINO PEDRO',
            'signatory_title' => $template->signatory_title ?: 'COMISSARIO',
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
        $name = $sample['name'] ?? 'Yuri Mendes Capenda';
        $documentNumber = $sample['document_number'] ?? $sample['number'] ?? 'ALN-001';

        return [
            'name' => $name,
            'initials' => $this->initials($name),
            'photo_url' => $template->sample_photo_url,
            'logo_url' => $template->logo_url ?: asset('images/logo-policia.png'),
            'institution_name' => $template->brand_name ?: 'SIGEF',
            'institution_location' => $sample['institution_location'] ?? $template->address_line,
            'brand_name' => $template->brand_name ?: 'SIGEF',
            'subtitle' => $template->subtitle,
            'front_title' => $template->front_title ?: 'PASSE DE ACESSO',
            'number_label' => $template->number_label ?: 'Nº',
            'card_number' => $sample['card_number'] ?? "001/CBP/EPP/{$academicYear}",
            'academic_year' => $academicYear,
            'access_until' => $sample['access_until'] ?? $this->defaultAccessUntil(),
            'entity_title' => $sample['entity_title'] ?? 'FORMANDO',
            'number' => $sample['number'] ?? $documentNumber,
            'course' => $sample['course'] ?? 'Curso de Ciencias Policiais',
            'class' => $sample['class'] ?? 'A/01',
            'cia' => $sample['cia'] ?? '1ª',
            'platoon' => $sample['platoon'] ?? '2º',
            'section' => $sample['section'] ?? '3ª',
            'document_label' => $sample['document_label'] ?? 'NURI',
            'document_number' => $documentNumber,
            'placement' => $sample['placement'] ?? null,
            'footer_text' => $template->footer_text ?: 'Este passe tem finalidade identificar o portador, permitir o seu acesso e permanencia a instituicao, na qualidade de Formando do SIGEF ate {access_until}',
            'signature_label' => $template->signature_label ?: 'O DIRECTOR',
            'signatory_name' => $template->signatory_name ?: 'DESTINO PEDRO',
            'signatory_title' => $template->signatory_title ?: 'COMISSARIO',
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
            'header_text_color' => '#001b4d',
            'back_text_color' => '#111827',
            'front_background_color' => '#ffffff',
            'back_background_color' => '#ffffff',
            'logo_path' => 'images/card-templates/student-cadet/iscpc-rnb-logo.png',
            'sample_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
            'fallback_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
            'signature_image_path' => 'images/cards/assinatura.png',
            'brand_name' => 'INSTITUTO SUPERIOR DE CIENCIAS POLICIAIS E CRIMINAIS',
            'subtitle' => '"GENERAL - OSVALDO DE JESUS SERRA VAN-DUNEM"',
            'front_title' => 'PASSE DE ACESSO',
            'number_label' => 'NIP/NURI',
            'back_title' => 'PRORROGATIVA',
            'signature_label' => 'O DIRECTOR',
            'signatory_name' => 'DESTINO PEDRO',
            'signatory_title' => 'COMISSARIO',
            'footer_text' => 'Este passe tem finalidade identificar o portador, permitir o seu acesso e permanencia a instituicao, na qualidade de Formando do SIGEF ate {access_until}',
            'show_qr_code' => true,
            'show_barcode' => false,
            'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
            'orientation' => CardTemplate::ORIENTATION_VERTICAL,
        ]);
    }

    protected function contextInstitution(Student $student, mixed $studentClass, ?int $institutionId = null): ?Institution
    {
        $institutionId = $this->allowedInstitutionId($student, $institutionId)
            ?: ($student->institution_id ?: $studentClass?->institution_id ?: null);

        if (! $institutionId) {
            return $student->institution ?: $studentClass?->institution;
        }

        if ((int) $student->institution_id === (int) $institutionId) {
            return $student->institution;
        }

        if ((int) ($studentClass?->institution_id ?? 0) === (int) $institutionId) {
            return $studentClass?->institution;
        }

        return Institution::query()->find($institutionId) ?: ($student->institution ?: $studentClass?->institution);
    }

    protected function allowedInstitutionId(Student $student, ?int $institutionId): ?int
    {
        if (! $institutionId) {
            return null;
        }

        $allowedIds = collect([$student->institution_id])
            ->merge($student->classEnrollments->pluck('institution_id'))
            ->merge($student->classEnrollments->pluck('studentClass.institution_id'))
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        return $allowedIds->contains((int) $institutionId)
            ? (int) $institutionId
            : null;
    }

    protected function defaultAccessUntil(): string
    {
        return 'Dezembro de '.((int) date('Y') + 2);
    }

    protected function studentPhotoUrl(Student $student, CardTemplate $template): ?string
    {
        if ($student->photo) {
            return PublicStorage::url($student->photo, requireExisting: true);
        }

        if ($student->candidate?->photo) {
            return PublicStorage::url($student->candidate->photo, requireExisting: true);
        }

        return null;
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

    protected function studentCardEntityTitle(Student $student): string
    {
        $studentType = Str::lower(Str::ascii((string) ($student->student_type ?? '')));

        if (str_contains($studentType, 'recruta')) {
            return 'RECRUTA';
        }

        if (str_contains($studentType, 'instruendo')) {
            return 'INSTRUENDO';
        }

        return 'FORMANDO';
    }

    protected function placementText(Student $student): ?string
    {
        $parts = [];

        if ($student->cia) {
            $parts[] = $this->formatCia($student->cia).' CIA';
        }

        if ($student->platoon) {
            $parts[] = $this->formatPlatoon($student->platoon).' Pel.';
        }

        if ($student->section) {
            $parts[] = $this->formatSection($student->section).' Sec.';
        }

        return $parts === [] ? null : implode(' / ', $parts);
    }

    protected function formatCia(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : (str_contains($value, 'ª') ? $value : "{$value}ª");
    }

    protected function formatPlatoon(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : (str_contains($value, 'º') ? $value : "{$value}º");
    }

    protected function formatSection(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : (str_contains($value, 'ª') ? $value : "{$value}ª");
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
