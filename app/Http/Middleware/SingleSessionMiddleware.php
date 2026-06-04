<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleSessionMiddleware
{
    /**
     * Verifica se o usuário está com sessão ativa em outro dispositivo.
     * A última sessão (novo login) sempre prevalece - sessões antigas são encerradas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = session()->getId();
            
            // Se acabou de fazer login, sincronizar o session_id e continuar
            if (session()->pull('just_logged_in')) {
                // O session_id pode ter mudado após regeneração do Laravel
                // Atualizar para garantir sincronização
                if ($user->current_session_id !== $currentSessionId) {
                    $user->update(['current_session_id' => $currentSessionId]);
                }
                return $next($request);
            }
            
            // Se não há current_session_id registrado, salvar o atual
            if (empty($user->current_session_id)) {
                $user->update(['current_session_id' => $currentSessionId]);
                return $next($request);
            }
            
            // Se o session_id registrado é diferente do atual, esta é uma sessão antiga
            // que deve ser encerrada porque o utilizador fez login noutro dispositivo
            if ($user->current_session_id !== $currentSessionId) {
                // Fazer logout desta sessão antiga
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirecionar para login com mensagem informativa
                return redirect('/login')
                    ->with('warning', 'A sua sessão foi encerrada porque iniciou sessão noutro dispositivo ou navegador.')
                    ->withErrors([
                        'email' => 'A sua sessão foi encerrada porque iniciou sessão noutro dispositivo ou navegador.',
                    ]);
            }
        }

        return $next($request);
    }
}
