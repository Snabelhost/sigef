<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddPermissionChecksToResources extends Command
{
    protected $signature = 'resources:add-permission-checks';
    protected $description = 'Adiciona verificação de permissões a todos os recursos de todos os painéis';

    public function handle()
    {
        $panels = [
            'admin' => 'App\\Filament\\Resources',
            'escola' => 'App\\Filament\\Escola\\Resources',
            'comando' => 'App\\Filament\\Comando\\Resources',
            'dpq' => 'App\\Filament\\Dpq\\Resources',
        ];

        $addedCount = 0;

        foreach ($panels as $panelName => $namespace) {
            $path = str_replace('\\', '/', $namespace);
            $path = str_replace('App/', 'app/', $path);
            $path = base_path($path);

            if (!File::isDirectory($path)) {
                $this->warn("Diretório não encontrado: {$path}");
                continue;
            }

            $files = File::files($path);

            foreach ($files as $file) {
                if (!str_ends_with($file->getFilename(), 'Resource.php')) {
                    continue;
                }

                $content = File::get($file->getPathname());
                $resourceName = str_replace('Resource.php', '', $file->getFilename());

                // Verificar se já tem canAccess
                if (str_contains($content, 'public static function canAccess()')) {
                    $this->line("  [OK] {$resourceName} já tem canAccess()");
                    continue;
                }

                // Verificar se tem shouldRegisterNavigation
                if (str_contains($content, 'public static function shouldRegisterNavigation()')) {
                    $this->line("  [OK] {$resourceName} já tem shouldRegisterNavigation()");
                    continue;
                }

                // Adicionar os métodos antes do último }
                $methodsToAdd = <<<PHP

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:{$resourceName}') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
PHP;

                // Encontrar a última } e inserir antes dela
                $lastBrace = strrpos($content, '}');
                if ($lastBrace !== false) {
                    $newContent = substr($content, 0, $lastBrace) . $methodsToAdd . "\n" . substr($content, $lastBrace);
                    File::put($file->getPathname(), $newContent);
                    $this->info("  [+] Adicionado canAccess() a {$panelName}/{$resourceName}");
                    $addedCount++;
                }
            }
        }

        $this->newLine();
        $this->info("Concluído! {$addedCount} recursos atualizados.");

        return 0;
    }
}
