<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InstitutionType;
use App\Models\Rank;
use App\Models\Provenance;
use App\Models\RecruitmentType;
use App\Models\AcademicYear;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tipos de Instituição
        $types = [
            ['name' => 'Direcção Nacional', 'description' => 'Órgãos centrais de direcção'],
            ['name' => 'Escola de Formação', 'description' => 'Estabelecimentos de ensino policial'],
            ['name' => 'Centro de Instrução', 'description' => 'Centros de treino especializado'],
            ['name' => 'Comando Provincial', 'description' => 'Unidades territoriais'],
        ];
        foreach ($types as $type) {
            InstitutionType::updateOrCreate(['name' => $type['name']], $type);
        }

        // 2. Patentes (Exemplo PNA)
        $ranks = [
            ['name' => 'Comissário Geral'],
            ['name' => 'Comissário Chefe'],
            ['name' => 'Comissário'],
            ['name' => 'Sub-Comissário'],
            ['name' => 'Superintendente-Chefe'],
            ['name' => 'Superintendente'],
            ['name' => 'Intendente'],
            ['name' => 'Inspector-Chefe'],
            ['name' => 'Inspector'],
            ['name' => 'Sub-Inspector'],
            ['name' => '1º Sub-Chefe'],
            ['name' => '2º Sub-Chefe'],
            ['name' => 'º Sub-Chefe'],
            ['name' => 'Agente de 1ª'],
            ['name' => 'Agente de 2ª'],
            ['name' => 'Agente de 3ª'],
        ];
        foreach ($ranks as $rank) {
            Rank::updateOrCreate(['name' => $rank['name']], $rank);
        }

        // 3. Províncias (Proveniência)
        $provinces = [
            ['name' => 'Bengo'],
            ['name' => 'Benguela'],
            ['name' => 'Bié'],
            ['name' => 'Cabinda'],
            ['name' => 'Cuando Cubango'],
            ['name' => 'Cuanza Norte'],
            ['name' => 'Cuanza Sul'],
            ['name' => 'Cunene'],
            ['name' => 'Huambo'],
            ['name' => 'Huíla'],
            ['name' => 'Luanda'],
            ['name' => 'Lunda Norte'],
            ['name' => 'Lunda Sul'],
            ['name' => 'Malanje'],
            ['name' => 'Moxico'],
            ['name' => 'Namibe'],
            ['name' => 'Uíge'],
            ['name' => 'Zaire'],
        ];
        foreach ($provinces as $province) {
            Provenance::updateOrCreate(['name' => $province['name']], $province);
        }

        // 4. Tipos de Recrutamento
        $recTypes = [
            ['name' => 'Concurso Público'],
            ['name' => 'Ingresso Directo'],
            ['name' => 'Promoção Interna'],
            ['name' => 'Reingresso'],
        ];
        foreach ($recTypes as $rec) {
            RecruitmentType::updateOrCreate(['name' => $rec['name']], $rec);
        }

        // 5. Ano Académico
        AcademicYear::updateOrCreate(
            ['year' => 2025 / 2027],
            ['name' => 'Ano Académico 2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => true]
        );
        AcademicYear::updateOrCreate(
            ['year' => 2026 / 2027],
            ['name' => 'Ano Académico 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => false]
        );
    }
}
