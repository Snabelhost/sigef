<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Criar tabela de Províncias
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->timestamps();
        });

        // Criar tabela de Municípios
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // Inserir Províncias de Angola
        $provinces = [
            ['name' => 'Bengo', 'code' => 'BGO'],
            ['name' => 'Benguela', 'code' => 'BGU'],
            ['name' => 'Bié', 'code' => 'BIE'],
            ['name' => 'Cabinda', 'code' => 'CAB'],
            ['name' => 'Cuando Cubango', 'code' => 'CCU'],
            ['name' => 'Cuanza Norte', 'code' => 'CNO'],
            ['name' => 'Cuanza Sul', 'code' => 'CUS'],
            ['name' => 'Cunene', 'code' => 'CNN'],
            ['name' => 'Huambo', 'code' => 'HUA'],
            ['name' => 'Huíla', 'code' => 'HUI'],
            ['name' => 'Luanda', 'code' => 'LUA'],
            ['name' => 'Lunda Norte', 'code' => 'LNO'],
            ['name' => 'Lunda Sul', 'code' => 'LSU'],
            ['name' => 'Malanje', 'code' => 'MAL'],
            ['name' => 'Moxico', 'code' => 'MOX'],
            ['name' => 'Namibe', 'code' => 'NAM'],
            ['name' => 'Uíge', 'code' => 'UIG'],
            ['name' => 'Zaire', 'code' => 'ZAI'],
        ];

        foreach ($provinces as $province) {
            DB::table('provinces')->insert(array_merge($province, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Inserir Municípios por Província
        $municipalities = [
            'Luanda' => ['Belas', 'Cacuaco', 'Cazenga', 'Icolo e Bengo', 'Luanda', 'Quiçama', 'Talatona', 'Viana'],
            'Bengo' => ['Ambriz', 'Bula Atumba', 'Dande', 'Dembos', 'Nambuangongo', 'Pango Aluquém'],
            'Benguela' => ['Balombo', 'Baía Farta', 'Benguela', 'Bocoio', 'Caimbambo', 'Catumbela', 'Chongorói', 'Cubal', 'Ganda', 'Lobito'],
            'Bié' => ['Andulo', 'Camacupa', 'Catabola', 'Chinguar', 'Chitembo', 'Cuemba', 'Cunhinga', 'Cuíto', 'Nharea'],
            'Cabinda' => ['Belize', 'Buco-Zau', 'Cabinda', 'Cacongo'],
            'Cuando Cubango' => ['Calai', 'Cuangar', 'Cuchi', 'Cuito Cuanavale', 'Dirico', 'Mavinga', 'Menongue', 'Nancova', 'Rivungo'],
            'Cuanza Norte' => ['Ambaca', 'Banga', 'Bolongongo', 'Cambambe', 'Cazengo', 'Golungo Alto', 'Gonguembo', 'Lucala', 'Quiculungo', 'Samba Caju'],
            'Cuanza Sul' => ['Amboim', 'Cassongue', 'Cela', 'Conda', 'Ebo', 'Libolo', 'Mussende', 'Porto Amboim', 'Quilenda', 'Quibala', 'Seles', 'Sumbe'],
            'Cunene' => ['Cahama', 'Cuanhama', 'Curoca', 'Cuvelai', 'Namacunde', 'Ombadja'],
            'Huambo' => ['Bailundo', 'Cachiungo', 'Caála', 'Ecunha', 'Huambo', 'Londuimbali', 'Longonjo', 'Mungo', 'Tchicala-Tcholoanga', 'Ucuma'],
            'Huíla' => ['Caconda', 'Cacula', 'Caluquembe', 'Chiange', 'Chibia', 'Chicomba', 'Chipindo', 'Cuvango', 'Gambos', 'Humpata', 'Jamba', 'Lubango', 'Matala', 'Quilengues'],
            'Lunda Norte' => ['Cambulo', 'Capenda-Camulemba', 'Caungula', 'Chitato', 'Cuango', 'Cuílo', 'Lubalo', 'Lucapa', 'Xá-Muteba'],
            'Lunda Sul' => ['Cacolo', 'Dala', 'Muconda', 'Saurimo'],
            'Malanje' => ['Cacuso', 'Calandula', 'Cambundi-Catembo', 'Cangandala', 'Caombo', 'Cuaba Nzoji', 'Cunda-Dia-Baze', 'Luquembo', 'Malanje', 'Marimba', 'Massango', 'Mucari', 'Quela', 'Quirima'],
            'Moxico' => ['Alto Zambeze', 'Bundas', 'Camanongue', 'Léua', 'Luacano', 'Luau', 'Luchazes', 'Luena', 'Moxico'],
            'Namibe' => ['Bibala', 'Camucuio', 'Moçâmedes', 'Tômbwa', 'Virei'],
            'Uíge' => ['Alto Cauale', 'Ambuíla', 'Bembe', 'Buengas', 'Bungo', 'Damba', 'Maquela do Zombo', 'Milunga', 'Mucaba', 'Negage', 'Puri', 'Quimbele', 'Quitexe', 'Sanza Pombo', 'Songo', 'Uíge'],
            'Zaire' => ['Cuimba', 'Mbanza Kongo', 'Nóqui', 'N\'Zeto', 'Soyo', 'Tomboco'],
        ];

        foreach ($municipalities as $provinceName => $municipalityList) {
            $province = DB::table('provinces')->where('name', $provinceName)->first();
            if ($province) {
                foreach ($municipalityList as $municipality) {
                    DB::table('municipalities')->insert([
                        'province_id' => $province->id,
                        'name' => $municipality,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('provinces');
    }
};
