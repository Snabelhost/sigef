<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AngolaAdministrativeDivisionsSeeder extends Seeder
{
    private const PROVINCE_CODES = [
        'Bengo' => 'BGO',
        'Benguela' => 'BGU',
        'Bié' => 'BIE',
        'Cabinda' => 'CAB',
        'Cuando' => 'CND',
        'Cuando Cubango' => 'CCU',
        'Cuanza Norte' => 'CNO',
        'Cuanza Sul' => 'CUS',
        'Cubango' => 'CUB',
        'Cunene' => 'CNN',
        'Huambo' => 'HUA',
        'Huíla' => 'HUI',
        'Icolo e Bengo' => 'IBG',
        'Luanda' => 'LUA',
        'Lunda Norte' => 'LNO',
        'Lunda Sul' => 'LSU',
        'Malanje' => 'MAL',
        'Moxico' => 'MOX',
        'Moxico Leste' => 'MXL',
        'Namibe' => 'NAM',
        'Uíge' => 'UIG',
        'Zaire' => 'ZAI',
    ];

    private const DIVISIONS = [
        'Bengo' => [
            'Ambriz',
            'Barra do Dande',
            'Bula Atumba',
            'Caxito',
            'Dande',
            'Muxaluando',
            'Nambuangongo',
            'Pango Aluquém',
            'Panguíla',
            'Piri',
            'Quibaxe',
            'Quicunzo',
            'Úcua',
        ],
        'Benguela' => [
            'Babaera',
            'Baía Farta',
            'Balombo',
            'Benguela',
            'Biópio',
            'Bocoio',
            'Bolonguera',
            'Caimbambo',
            'Canhamela',
            'Capupa',
            'Catengue',
            'Catumbela',
            'Chicuma',
            'Chila',
            'Chindumbo',
            'Chongorói',
            'Cubal',
            'Dombe Grande',
            'Egito Praia',
            'Ganda',
            'Iambala',
            'Lobito',
            'Navegantes',
        ],
        'Bié' => [
            'Andulo',
            'Belo Horizonte',
            'Calucinga',
            'Camacupa',
            'Cambândua',
            'Catabola',
            'Chicala',
            'Chinguar',
            'Chipeta',
            'Chissamba',
            'Chitembo',
            'Cuemba',
            'Cuito',
            'Cunhinga',
            'Luando',
            'Lúbia',
            'Mumbué',
            'Nharêa',
            'Ringoma',
            'Umpulo',
        ],
        'Cabinda' => [
            'Belize',
            'Buco Zau',
            'Cabinda',
            'Cacongo',
            'Liambo',
            'Massabi',
            'Miconje',
            'Necuto',
            'Ngoio',
            'Tando Zinze',
        ],
        'Cuando' => [
            'Cuito Cuanavale',
            'Dima',
            'Dirico',
            'Luengue',
            'Luiana',
            'Mavinga',
            'Mucusso',
            'Rivungo',
            'Xipundo',
        ],
        'Cuando Cubango' => [
            'Menongue',
        ],
        'Cuanza Norte' => [
            'Aldeia Nova',
            'Ambaca',
            'Banga',
            'Bolongongo',
            'Caculo Cabaça',
            'Camabatela',
            'Cambambe',
            'Cazengo',
            'Cêrca',
            'Golungo Alto',
            'Lucala',
            'Luinga',
            'Massangano',
            'N’dalatando',
            'Ngonguembo',
            'Quiculungo',
            'Samba Caju',
            'Tango',
            'Terreiro',
        ],
        'Cuanza Sul' => [
            'Amboiva',
            'Boa Entrada',
            'Calulo',
            'Cassongue',
            'Conda',
            'Condé',
            'Ebo',
            'Gabela',
            'Gangula',
            'Gungo',
            'Lonhe',
            'Munenga',
            'Mussende',
            'Pambangala',
            'Porto Amboím',
            'Quenha',
            'Quibala',
            'Quilenda',
            'Quirimbo',
            'Quissongo',
            'Sanga',
            'Seles',
            'Sumbe',
            'Uacu Cungo',
            'Waku Kungo',
        ],
        'Cubango' => [
            'Caiundo',
            'Calai',
            'Chinguanja',
            'Cuangar',
            'Cuchi',
            'Cutato',
            'Longa',
            'Mavengue',
            'Menongue',
            'Nancova',
            'Savate',
        ],
        'Cunene' => [
            'Cafima',
            'Cahama',
            'Chiéde',
            'Chissuata',
            'Chitado',
            'Cuanhama',
            'Curoca',
            'Cuvelai',
            'Humbe',
            'Mupa',
            'Namacunde',
            'Naulila',
            'Nehone',
            'Ombadja',
            'Ondjiva',
        ],
        'Huambo' => [
            'Alto Hama',
            'Bailundo',
            'Bimbe',
            'Caála',
            'Cachiungo',
            'Chela',
            'Chicala Choloanga',
            'Chilata',
            'Chinjenje',
            'Cuima',
            'Ecunha',
            'Galanga',
            'Huambo',
            'Londuimbali',
            'Longonjo',
            'Mungo',
            'Sambo',
            'Ucuma',
        ],
        'Huíla' => [
            'Caconda',
            'Cacula',
            'Caluquembe',
            'Capelongo',
            'Capunda Cavilongo',
            'Chibia',
            'Chicomba',
            'Chicungo',
            'Chipindo',
            'Chituto',
            'Cuvango',
            'Dongo',
            'Galangue',
            'Gambos',
            'Hoque',
            'Humpata',
            'Jamba',
            'Jamba Mineira',
            'Lubango',
            'Matala',
            'Palanca',
            'Quilengues',
            'Quipungo',
            'Viti Vivali',
        ],
        'Icolo e Bengo' => [
            'Bom Jesus',
            'Cabiri',
            'Cabo Ledo',
            'Calumbo',
            'Catete',
            'Quiçama',
            'Sequele',
        ],
        'Luanda' => [
            'Belas',
            'Cacuaco',
            'Camama',
            'Cazenga',
            'Hoji ya Henda',
            'Icolo e Bengo',
            'Ingombota',
            'Kilamba',
            'Kilamba Kiaxi',
            'Luanda',
            'Maianga',
            'Mulenvos',
            'Mussulo',
            'Rangel',
            'Samba',
            'Sambizanga',
            'Talatona',
            'Viana',
        ],
        'Lunda Norte' => [
            'Cafunfo',
            'Camaxilo',
            'Cambulo',
            'Canzar',
            'Capenda Camulemba',
            'Cassanje Calucala',
            'Caungula',
            'Chitato',
            'Cuango',
            'Cuilo',
            'Dundo',
            'Lóvua',
            'Luangue',
            'Lubalo',
            'Lucapa',
            'Luremo',
            'Mussungue',
            'Xá Cassau',
            'Xá Muteba',
        ],
        'Lunda Sul' => [
            'Alto Chicapa',
            'Cacolo',
            'Cassai-Sul',
            'Cassengo',
            'Cazage',
            'Cazaji',
            'Chiluage',
            'Dala',
            'Luma Cassai',
            'Muangueji',
            'Muconda',
            'Muriege',
            'Saurimo',
            'Sombo',
            'Xassengue',
        ],
        'Malanje' => [
            'Caculama',
            'Cacuso',
            'Cahombo',
            'Calandula',
            'Cambo Suinginge',
            'Cambundi Catembo',
            'Cangandala',
            'Capunda',
            'Cateco Cangola',
            'Cuale',
            'Kiwaba Nzoji',
            'Kunda dya Baze',
            'Luquembo',
            'Malanje',
            'Marimba',
            'Massango',
            'Mbanji ya Ngola',
            'Milando',
            'Muquixe',
            'Ngola Luiji',
            'Pungu a Ndongo',
            'Quela',
            'Quêssua',
            'Quihuhu',
            'Quirima',
            'Quitapa',
            'Xandel',
        ],
        'Moxico' => [
            'Alto Cuito',
            'Camanongue',
            'Cangamba',
            'Cangumbe',
            'Chiúme',
            'Léua',
            'Luau',
            'Lucusse',
            'Luena',
            'Lumbala Nguimbo',
            'Lumeje',
            'Lutembo',
            'Lutuai',
            'Ninda',
        ],
        'Moxico Leste' => [
            'Caianda',
            'Cameia',
            'Cazombo',
            'Lago Dilolo',
            'Lóvua do Zambeze',
            'Luacano',
            'Luau',
            'Macondo',
            'Nana Candundo',
        ],
        'Namibe' => [
            'Bibala',
            'Cacimbas',
            'Camucuio',
            'Iona',
            'Lucira',
            'Moçâmedes',
            'Sacomar',
            'Tômbwa',
            'Virei',
        ],
        'Uíge' => [
            'Alto Zaza',
            'Ambuíla',
            'Bembe',
            'Bungo',
            'Cangola',
            'Damba',
            'Dange Quitexe',
            'Lucunga',
            'Maquela do Zombo',
            'Massau',
            'Milunga',
            'Mucaba',
            'Negage',
            'Nova Esperança',
            'Nsosso',
            'Puri',
            'Quimbele',
            'Quipedro',
            'Sacandica',
            'Sanza Pombo',
            'Songo',
            'Uíge',
            'Vista Alegre',
        ],
        'Zaire' => [
            'Cuimba',
            'Lufico',
            'Luvo',
            'Mbanza Congo',
            'Mbanza Kongo',
            'N\'zeto',
            'Nóqui',
            'Nzeto',
            'Quêlo',
            'Quindeje',
            'Serra de Canda',
            'Soio',
            'Soyo',
            'Tomboco',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $referencedMunicipalityIds = $this->referencedIds('municipality_id');
            $matchedMunicipalityIds = [];

            foreach (self::DIVISIONS as $provinceName => $municipalityNames) {
                $province = $this->findProvince($provinceName);
                $province->fill([
                    'name' => $provinceName,
                    'code' => self::PROVINCE_CODES[$provinceName] ?? $province->code,
                ])->save();

                $existingMunicipalities = Municipality::query()
                    ->where('province_id', $province->id)
                    ->get();

                foreach ($municipalityNames as $municipalityName) {
                    $municipality = $this->findMunicipality($existingMunicipalities, $municipalityName, $matchedMunicipalityIds);

                    if (! $municipality) {
                        $municipality = new Municipality();
                    }

                    $municipality->fill([
                        'province_id' => $province->id,
                        'name' => $municipalityName,
                    ])->save();

                    $matchedMunicipalityIds[] = $municipality->id;

                    if (! $existingMunicipalities->contains('id', $municipality->id)) {
                        $existingMunicipalities->push($municipality);
                    }
                }

                Municipality::query()
                    ->where('province_id', $province->id)
                    ->whereNotIn('id', $matchedMunicipalityIds)
                    ->whereNotIn('id', $referencedMunicipalityIds)
                    ->delete();
            }
        });
    }

    private function findProvince(string $name): Province
    {
        $normalized = $this->normalize($name);

        return Province::query()
            ->get()
            ->first(fn (Province $province): bool => $this->normalize($province->name) === $normalized)
            ?? new Province();
    }

    /**
     * @param \Illuminate\Support\Collection<int, Municipality> $municipalities
     * @param array<int, int> $matchedIds
     */
    private function findMunicipality($municipalities, string $name, array $matchedIds): ?Municipality
    {
        $exact = $municipalities->first(
            fn (Municipality $municipality): bool => ! in_array($municipality->id, $matchedIds, true)
                && $municipality->name === $name
        );

        if ($exact) {
            return $exact;
        }

        $normalized = $this->normalize($name);

        return $municipalities->first(
            fn (Municipality $municipality): bool => ! in_array($municipality->id, $matchedIds, true)
                && $this->normalize($municipality->name) === $normalized
        );
    }

    /**
     * @return array<int, int>
     */
    private function referencedIds(string $column): array
    {
        $ids = [];

        foreach (['candidates'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $ids = array_merge(
                $ids,
                DB::table($table)
                    ->whereNotNull($column)
                    ->distinct()
                    ->pluck($column)
                    ->map(fn ($id): int => (int) $id)
                    ->all()
            );
        }

        return array_values(array_unique($ids));
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/i', '', Str::ascii(Str::lower(trim($value)))) ?? '';
    }
}
