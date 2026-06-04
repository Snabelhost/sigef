<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldAudits extends Command
{
    protected $signature = 'audits:clean {--days=90 : Número de dias a manter}';
    protected $description = 'Limpar registos de auditoria mais antigos que N dias';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = DB::table('audits')
            ->where('created_at', '<', $cutoff)
            ->count();

        if ($count === 0) {
            $this->info('Nenhum registo de auditoria antigo encontrado.');
            return self::SUCCESS;
        }

        $this->info("A eliminar {$count} registos de auditoria anteriores a {$cutoff->toDateString()}...");

        DB::table('audits')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("✅ {$count} registos eliminados com sucesso.");
        return self::SUCCESS;
    }
}
