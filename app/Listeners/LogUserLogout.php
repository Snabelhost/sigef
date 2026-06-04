<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\ActivityLog;

class LogUserLogout
{
    /**
     * Registra atividade quando o utilizador faz logout
     */
    public function handle(Logout $event): void
    {
        if ($event->user) {
            // Registrar atividade de logout
            ActivityLog::create([
                'user_id' => $event->user->id,
                'action' => 'logout',
                'module' => 'Autenticação',
                'description' => 'Utilizador encerrou sessão no sistema',
                'ip_address' => request()->ip(),
                'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 500),
                'device_type' => ActivityLog::detectDeviceType(request()->userAgent() ?? ''),
                'browser' => ActivityLog::detectBrowser(request()->userAgent() ?? ''),
                'platform' => ActivityLog::detectPlatform(request()->userAgent() ?? ''),
                'url' => mb_substr(request()->fullUrl(), 0, 500),
                'method' => request()->method(),
            ]);
            
            // Limpar sessão do usuário
            $event->user->update([
                'current_session_id' => null,
            ]);
        }
    }
}
