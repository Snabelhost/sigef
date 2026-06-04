<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Garantir que a sessão seja limpa completamente
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Sempre redirecionar para a página de login unificada
        return redirect('/login');
    }
}
