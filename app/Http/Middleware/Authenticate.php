<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

/**
 * Middleware de autenticação customizado que redireciona para /login unificado
 * em vez do login específico de cada painel Filament.
 */
class Authenticate extends FilamentAuthenticate
{
    /**
     * Redirecionar para a rota de login unificada.
     */
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
