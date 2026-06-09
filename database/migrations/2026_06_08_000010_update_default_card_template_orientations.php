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

        $templates = [
            [
                'lookup' => [
                    'card_type' => CardTemplate::TYPE_STUDENT,
                    'name' => 'Cartao de Formando',
                ],
                'values' => [
                    'name' => 'Cartao de Formando',
                    'card_type' => CardTemplate::TYPE_STUDENT,
                    'is_default' => true,
                    'is_active' => true,
                    'primary_color' => '#061b42',
                    'secondary_color' => '#ef233c',
                    'text_color' => '#061b42',
                    'front_text_color' => '#061b42',
                    'back_text_color' => '#111827',
                    'front_background_color' => '#ffffff',
                    'back_background_color' => '#ffffff',
                    'logo_path' => 'images/card-templates/student-cadet/cadete-logo.png',
                    'front_background_path' => 'images/cards/fundo_card.png',
                    'back_background_path' => 'images/cards/fundo_card.png',
                    'sample_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
                    'signature_image_path' => 'images/cards/assinatura.png',
                    'brand_name' => 'SIGEF',
                    'subtitle' => 'Sistema Integrado de Gestao de Formacao',
                    'front_title' => 'CARTAO DO FORMANDO',
                    'number_label' => 'NIP/NURI',
                    'back_title' => 'Identificacao do Formando',
                    'footer_text' => 'Este cartao identifica o portador na qualidade de formando.',
                    'show_qr_code' => true,
                    'show_barcode' => false,
                    'style' => CardTemplate::STYLE_STUDENT,
                    'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                    'updated_at' => $timestamp,
                ],
            ],
            [
                'lookup' => [
                    'card_type' => CardTemplate::TYPE_PROFESSOR,
                    'name' => 'Cartao de Formador',
                ],
                'values' => [
                    'name' => 'Cartao de Formador',
                    'card_type' => CardTemplate::TYPE_PROFESSOR,
                    'is_default' => true,
                    'is_active' => true,
                    'primary_color' => '#061b42',
                    'secondary_color' => '#ef233c',
                    'text_color' => '#061b42',
                    'front_text_color' => '#061b42',
                    'back_text_color' => '#111827',
                    'front_background_color' => '#ffffff',
                    'back_background_color' => '#ffffff',
                    'logo_path' => 'images/card-templates/student-cadet/iscpc-rnb-logo.png',
                    'front_background_path' => 'images/card-templates/student-cadet/front-template.png',
                    'back_background_path' => 'images/card-templates/student-cadet/back-template.png',
                    'sample_photo_path' => 'images/card-templates/student-cadet/sample-photo.png',
                    'brand_name' => 'SIGEF',
                    'subtitle' => 'Sistema Integrado de Gestao de Formacao',
                    'front_title' => 'CARTAO DO FORMADOR',
                    'number_label' => 'NIP/BI',
                    'back_title' => 'Identificacao do Formador',
                    'footer_text' => 'Este cartao identifica o portador na qualidade de formador.',
                    'show_qr_code' => true,
                    'show_barcode' => false,
                    'style' => CardTemplate::STYLE_PROFESSOR_VERTICAL,
                    'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                    'updated_at' => $timestamp,
                ],
            ],
            [
                'lookup' => [
                    'card_type' => CardTemplate::TYPE_STAFF,
                    'name' => 'Cartao de Efectivo',
                    'card_variant' => 'with_department',
                ],
                'values' => [
                    'name' => 'Cartao de Efectivo',
                    'card_type' => CardTemplate::TYPE_STAFF,
                    'card_variant' => 'with_department',
                    'is_default' => true,
                    'is_active' => true,
                    'primary_color' => '#000b52',
                    'secondary_color' => '#ff1208',
                    'text_color' => '#000b52',
                    'front_text_color' => '#000b52',
                    'back_text_color' => '#111827',
                    'front_background_color' => '#ffffff',
                    'back_background_color' => '#ffffff',
                    'logo_path' => 'images/card-templates/staff-effective/iscpc-rnb-logo.png',
                    'front_background_path' => 'images/card-templates/staff-effective/front-template.png',
                    'back_background_path' => 'images/card-templates/student-cadet/back-template.png',
                    'sample_photo_path' => 'images/card-templates/staff-effective/sample-photo.png',
                    'brand_name' => 'POLICIA NACIONAL DE ANGOLA',
                    'subtitle' => 'INSTITUTO SUPERIOR DE CIENCIAS POLICIAIS E CRIMINAIS',
                    'front_title' => 'CARTAO DO EFECTIVO',
                    'number_label' => 'NIP/BI',
                    'back_title' => 'Identificacao do Efectivo',
                    'footer_text' => 'Este cartao identifica o portador na qualidade de efectivo.',
                    'show_qr_code' => true,
                    'show_barcode' => false,
                    'style' => CardTemplate::STYLE_STAFF_EFFECTIVE,
                    'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                    'updated_at' => $timestamp,
                ],
            ],
        ];

        foreach ($templates as $template) {
            DB::table('card_templates')
                ->where('card_type', $template['values']['card_type'])
                ->update(['is_default' => false]);

            DB::table('card_templates')->updateOrInsert(
                $template['lookup'],
                array_merge($template['values'], [
                    'created_at' => $timestamp,
                ]),
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')
            ->where('card_type', CardTemplate::TYPE_STUDENT)
            ->update([
                'name' => 'Cartao de Aluno',
                'orientation' => CardTemplate::ORIENTATION_HORIZONTAL,
                'updated_at' => now(),
            ]);

        DB::table('card_templates')
            ->where('card_type', CardTemplate::TYPE_PROFESSOR)
            ->update([
                'name' => 'Cartao de Professor',
                'orientation' => CardTemplate::ORIENTATION_VERTICAL,
                'updated_at' => now(),
            ]);
    }
};
