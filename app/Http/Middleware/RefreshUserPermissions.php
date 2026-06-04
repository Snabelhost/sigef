<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\PermissionRegistrar;

class RefreshUserPermissions
{
    /**
     * Recarrega as permissões do usuário a cada request.
     * Isso garante que alterações em roles sejam aplicadas imediatamente.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // 1. Limpa o cache interno do modelo de permissões
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            
            // 2. Remove o cache de permissões do modelo
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
            
            // 3. Recarrega as relações frescas do banco de dados
            $user->load(['roles' => function ($query) {
                $query->with('permissions');
            }]);
            
            // 4. Força o Spatie Permission a limpar seu cache interno
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $next($request);
    }
}
