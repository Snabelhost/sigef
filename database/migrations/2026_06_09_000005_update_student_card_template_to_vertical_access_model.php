<?php

use App\Models\CardTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        $timestamp = now();

        DB::table('card_templates')
            ->where('card_type', CardTemplate::TYPE_STUDENT)
            ->update([
                'is_default' => false,
                'updated_at' => $timestamp,
            ]);

        DB::table('card_templates')->updateOrInsert(
            [
                'card_type' => CardTemplate::TYPE_STUDENT,
                'name' => 'Cartao de Formando',
            ],
            [
                'name' => 'Cartao de Formando',
                'card_type' => CardTemplate::TYPE_STUDENT,
                'card_variant' => null,
                'is_default' => true,
                'is_active' => true,
                'primary_color' => '#061b42',
                'secondary_color' => '#ff1208',
                'text_color' => '#061b42',
                'front_text_color' => '#061b42',
                'back_text_color' => '#111827',
                'front_background_color' => '#ffffff',
                'back_background_color' => '#ffffff',
                'logo_path' => 'images/card-templates/student-cadet/iscpc-rnb-logo.png',
                'front_background_path' => null,
                'back_background_path' => null,
                'signature_image_path' => 'images/cards/assinatura.png',
                'sample_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
                'fallback_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
                'brand_name' => 'INSTITUTO SUPERIOR DE CIENCIAS POLICIAIS E CRIMINAIS',
                'subtitle' => '"GENERAL - OSVALDO DE JESUS SERRA VAN-DUNEM"',
                'front_title' => 'PASSE DE ACESSO',
                'number_label' => 'NIP/NURI',
                'back_title' => 'PRORROGATIVA',
                'signature_label' => 'O DIRECTOR',
                'signatory_name' => 'DESTINO PEDRO',
                'signatory_title' => 'COMISSARIO',
                'footer_text' => 'Este passe tem finalidade identificar o portador, permitir o seu acesso e permanencia a instituicao, na qualidade de Formando do SIGEF ate {access_until}',
                'sample_payload' => json_encode([
                    'name' => 'Yuri Mendes Capenda',
                    'number' => 'FORM-001',
                    'document_label' => 'NIP',
                    'document_number' => 'FORM-001',
                    'course' => 'Curso de Ciencias Policiais',
                    'class' => 'A/01',
                    'entity_title' => 'FORMANDO',
                    'access_until' => 'Dezembro de '.((int) date('Y') + 2),
                ], JSON_UNESCAPED_SLASHES),
                'show_qr_code' => true,
                'show_barcode' => false,
                'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
                'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')
            ->where('card_type', CardTemplate::TYPE_STUDENT)
            ->where('name', 'Cartao de Formando')
            ->update([
                'style' => CardTemplate::STYLE_STUDENT,
                'front_title' => 'CARTAO DO FORMANDO',
                'back_title' => 'Identificacao do Formando',
                'front_background_path' => 'images/cards/fundo_card.png',
                'back_background_path' => 'images/cards/fundo_card.png',
                'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                'updated_at' => now(),
            ]);
    }
};
