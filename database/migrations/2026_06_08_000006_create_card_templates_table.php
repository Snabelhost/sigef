<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            Schema::create('card_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('card_type', 40);
                $table->string('card_variant', 40)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('primary_color', 20)->default('#041c4f');
                $table->string('secondary_color', 20)->default('#0ea5e9');
                $table->string('text_color', 20)->default('#ffffff');
                $table->string('front_text_color', 20)->nullable();
                $table->string('back_text_color', 20)->nullable();
                $table->string('front_background_color', 20)->nullable();
                $table->string('back_background_color', 20)->nullable();
                $table->string('logo_path')->nullable();
                $table->string('front_background_path')->nullable();
                $table->string('back_background_path')->nullable();
                $table->string('signature_image_path')->nullable();
                $table->string('sample_photo_path')->nullable();
                $table->json('sample_payload')->nullable();
                $table->string('brand_name')->nullable();
                $table->string('subtitle')->nullable();
                $table->string('website')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 50)->nullable();
                $table->string('contact_whatsapp', 50)->nullable();
                $table->string('address_line')->nullable();
                $table->string('back_title')->nullable();
                $table->string('signature_label')->nullable();
                $table->string('signatory_name')->nullable();
                $table->string('signatory_title')->nullable();
                $table->text('footer_text')->nullable();
                $table->boolean('show_qr_code')->default(true);
                $table->string('style', 40)->default('modern');
                $table->string('orientation', 30)->default('horizontal');
                $table->timestamps();

                $table->index(['card_type', 'is_active', 'is_default']);
                $table->index(['card_type', 'card_variant']);
            });
        }

        $timestamp = now();
        $defaults = [
            'is_default' => true,
            'is_active' => true,
            'brand_name' => 'SIGEF',
            'subtitle' => 'Sistema Integrado de Gestao de Formacao',
            'show_qr_code' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $templates = [
            [
                'name' => 'Cartao de Aluno',
                'card_type' => 'student',
                'primary_color' => '#041c4f',
                'secondary_color' => '#0ea5e9',
                'text_color' => '#ffffff',
                'front_background_color' => '#041c4f',
                'back_background_color' => '#eef4ff',
                'back_title' => 'Identificacao',
                'footer_text' => 'Este cartao identifica o portador na qualidade de aluno/formando.',
                'style' => 'student',
                'orientation' => 'horizontal',
            ],
            [
                'name' => 'Cartao de Professor',
                'card_type' => 'professor',
                'primary_color' => '#05255f',
                'secondary_color' => '#2563eb',
                'text_color' => '#ffffff',
                'front_background_color' => '#05255f',
                'back_background_color' => '#eff6ff',
                'back_title' => 'Passe de Acesso',
                'footer_text' => 'Este cartao identifica o portador na qualidade de professor/formador.',
                'style' => 'professor_vertical',
                'orientation' => 'vertical',
            ],
            [
                'name' => 'Cartao de Efectivo',
                'card_type' => 'staff',
                'card_variant' => 'with_department',
                'primary_color' => '#063970',
                'secondary_color' => '#38bdf8',
                'text_color' => '#ffffff',
                'front_text_color' => '#001b4d',
                'back_text_color' => '#111827',
                'front_background_color' => '#f8fafc',
                'back_background_color' => '#f8fafc',
                'back_title' => 'Prerrogativa',
                'footer_text' => 'Este passe e pessoal e intransmissivel, destinado a identificar o portador na qualidade de efectivo.',
                'style' => 'staff_effective',
                'orientation' => 'horizontal',
            ],
        ];

        foreach ($templates as $template) {
            DB::table('card_templates')->updateOrInsert(
                ['card_type' => $template['card_type'], 'name' => $template['name']],
                array_merge($defaults, $template, ['updated_at' => $timestamp]),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('card_templates');
    }
};
