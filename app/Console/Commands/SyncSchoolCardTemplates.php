<?php

namespace App\Console\Commands;

use App\Services\SchoolCardTemplateSyncService;
use Illuminate\Console\Command;

class SyncSchoolCardTemplates extends Command
{
    protected $signature = 'card-templates:sync-schools';

    protected $description = 'Copia os modelos globais de cartoes para todas as escolas.';

    public function handle(SchoolCardTemplateSyncService $syncService): int
    {
        $result = $syncService->syncGlobalTemplatesToAllSchools();

        $this->info('Modelos de cartoes sincronizados para as escolas.');
        $this->table(
            ['Modelos globais', 'Escolas', 'Criados', 'Ja existentes'],
            [[
                $result['templates'],
                $result['institutions'],
                $result['created'],
                $result['existing'],
            ]],
        );

        return Command::SUCCESS;
    }
}
