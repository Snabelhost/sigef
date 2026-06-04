<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Institution;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@sigef.com'],
            [
                'name' => 'Super Administrador',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        // Atribuir papel de super_admin se o Shield estiver instalado
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $admin->assignRole($role);
        }

        // 2. Criar Usuário de Escola (Tenant)
        // Garantir que a instituição existe (usando uma das criadas no TestDataSeeder)
        $institution = Institution::where('acronym', 'EFQP-LDA')->first();
        
        if (!$institution) {
            // Criar se não existir (fallback)
            $type = \App\Models\InstitutionType::first();
            $institution = Institution::create([
                'name' => 'Escola de Formação de Quadros de Polícia - Luanda',
                'acronym' => 'EFQP-LDA',
                'institution_type_id' => $type->id ?? 1,
                'province' => 'Luanda',
                'is_active' => true,
            ]);
        }
        
        if ($institution) {
            $schoolUser = User::firstOrCreate(
                ['email' => 'escola@sigef.com'],
                [
                    'name' => 'Admin Escola Luanda',
                    'password' => bcrypt('password'),
                    'institution_id' => $institution->id,
                    'is_active' => true,
                ]
            );

            // Papel para o usuário da escola
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                // No SIGEF, o painel de escola costuma usar papéis específicos
                // Vou garantir que ele tenha acesso ao painel
                $role = Role::firstOrCreate(['name' => 'escola_admin', 'guard_name' => 'web']);
                $schoolUser->assignRole($role);
            }
        }

        echo "✅ Acessos criados:\n";
        echo "   - Super Admin: admin@sigef.com / password\n";
        echo "   - Escola Admin: escola@sigef.com / password\n";
    }
}
