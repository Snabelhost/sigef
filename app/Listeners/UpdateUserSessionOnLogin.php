<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\ActivityLog;

class UpdateUserSessionOnLogin
{
    /**
     * Quando o usuário faz login, registra a nova sessão.
     * Qualquer sessão anterior em outros dispositivos será automaticamente invalidada
     * pelo SingleSessionMiddleware quando tentar aceder ao sistema.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        // Marcar que este é um login "fresco" para o middleware não bloquear
        session()->put('just_logged_in', true);
        
        // Atualizar o session_id atual do usuário - isto invalida sessões anteriores
        $user->update([
            'current_session_id' => session()->getId(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
        
        // Registrar atividade de login
        ActivityLog::log(
            action: 'login',
            module: 'Autenticação',
            description: 'Utilizador iniciou sessão no sistema'
        );
    }
}
