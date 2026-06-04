<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyPolicies extends Command
{
    protected $signature = 'policies:verify';
    protected $description = 'Verifica se todos os recursos têm policies configuradas';

    public function handle()
    {
        $resourcePath = app_path('Filament/Resources');
        $policyPath = app_path('Policies');
        
        $resources = collect(File::files($resourcePath))
            ->filter(fn($file) => str_ends_with($file->getFilename(), 'Resource.php'))
            ->map(fn($file) => str_replace('Resource.php', '', $file->getFilename()));
        
        $policies = collect(File::files($policyPath))
            ->filter(fn($file) => str_ends_with($file->getFilename(), 'Policy.php'))
            ->map(fn($file) => str_replace('Policy.php', '', $file->getFilename()));
        
        $this->info("Total de Resources: " . $resources->count());
        $this->info("Total de Policies: " . $policies->count());
        $this->newLine();
        
        $tableData = [];
        $missing = [];
        
        foreach ($resources as $resource) {
            $hasPolicy = $policies->contains($resource);
            $tableData[] = [
                $resource,
                $hasPolicy ? '✅ SIM' : '❌ NÃO',
            ];
            
            if (!$hasPolicy) {
                $missing[] = $resource;
            }
        }
        
        $this->table(['Resource', 'Tem Policy?'], $tableData);
        
        if (count($missing) > 0) {
            $this->newLine();
            $this->warn("Policies em falta: " . count($missing));
            foreach ($missing as $m) {
                $this->line("  - {$m}Policy.php");
            }
        } else {
            $this->newLine();
            $this->info("✅ Todos os resources têm policies configuradas!");
        }
        
        return 0;
    }
}
