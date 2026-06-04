<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;

class FilamentTableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuração global de paginação para todas as tabelas do Filament
        // Usando 5 como padrão com opções [5, 10, 25, 50, 'all']
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(5)
                ->paginationPageOptions([5, 10, 25, 50, 'all']);
        });
    }
}

