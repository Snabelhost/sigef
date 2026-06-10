<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UnifiedLoginController extends Controller
{
    /**
     * Mostrar o formulário de login unificado.
     */
    public function showLoginForm()
    {
        // Se já estiver autenticado, redirecionar para o painel correto
        if (Auth::check()) {
            return $this->redirectToPanel(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Processar o login.
     */
    public function login(Request $request)
    {
        // Rate limiting: máx 5 tentativas por minuto por IP
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Demasiadas tentativas de login. Tente novamente em {$seconds} segundos.",
            ]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Introduza um email válido.',
            'password.required' => 'A senha é obrigatória.',
        ]);

        // Verificar se o utilizador existe e está ativo
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user && !$user->is_active) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'As credenciais fornecidas não correspondem aos nossos registos.',
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            // Após regenerar a sessão, atualizar o session_id do utilizador
            // e marcar como login fresco para o SingleSessionMiddleware não bloquear.
            // Isto é necessário porque o evento Login (que dispara UpdateUserSessionOnLogin)
            // ocorre ANTES da regeneração da sessão, causando um session_id desatualizado.
            $user = Auth::user();
            session()->put('just_logged_in', true);
            $user->update(['current_session_id' => session()->getId()]);

            return $this->redirectToPanel($user);
        }

        RateLimiter::hit($throttleKey);

        throw ValidationException::withMessages([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registos.',
        ]);
    }

    /**
     * Encerrar sessão.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Obter a lista de painéis que o utilizador pode aceder.
     */
    protected function getAccessiblePanels($user): array
    {
        $accessiblePanels = [];

        $panels = [
            'admin' => ['name' => 'Administração', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => '/admin'],
            'escola' => ['name' => 'Escola', 'icon' => 'heroicon-o-academic-cap', 'url' => $user->institution_id ? '/escola/' . $user->institution_id : '/escola'],
            'aluno' => ['name' => 'Aluno', 'icon' => 'heroicon-o-user', 'url' => '/aluno'],
            'professores' => ['name' => 'Professores', 'icon' => 'heroicon-o-user-group', 'url' => '/professores'],
        ];

        foreach ($panels as $panelId => $panelInfo) {
            try {
                $panel = \Filament\Facades\Filament::getPanel($panelId);

                // Verificar se o utilizador tem acesso explícito a este painel
                $hasExplicitAccess = false;

                // Admin panel - super_admin ou admin ou panel_user
                if ($panelId === 'admin') {
                    if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('panel_user') || $user->hasRole('admin_admin')) {
                        $hasExplicitAccess = true;
                    }
                }

                // Escola panel - precisa role E institution_id (escola é específica)
                if ($panelId === 'escola') {
                    if (($user->hasRole('escola_admin') || $user->hasRole('escola_user')) && $user->institution_id) {
                        $hasExplicitAccess = true;
                    }
                }

                // Aluno panel
                if ($panelId === 'aluno') {
                    if ($user->hasRole('aluno_admin') || $user->hasRole('aluno_user')) {
                        $hasExplicitAccess = true;
                    }
                }

                // Professores panel
                if ($panelId === 'professores') {
                    if ($user->hasRole('professores_admin') || $user->hasRole('professores_user')) {
                        $hasExplicitAccess = true;
                    }
                }

                if ($hasExplicitAccess) {
                    $accessiblePanels[$panelId] = $panelInfo;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Se super_admin não tem nenhum painel específico, dar acesso ao admin
        if ($user->hasRole('super_admin') && empty($accessiblePanels)) {
            $accessiblePanels['admin'] = $panels['admin'];
        }

        return $accessiblePanels;
    }

    /**
     * Redirecionar o utilizador para o painel correto baseado no role.
     */
    public function redirectToPanel($user)
    {
        // Limpar sessão intended para evitar redirecionamentos para URLs antigas
        session()->forget('url.intended');

        // Obter painéis acessíveis
        $accessiblePanels = $this->getAccessiblePanels($user);

        // Se o utilizador tem acesso a mais de um painel, mostrar página de selecção
        if (count($accessiblePanels) > 1) {
            return redirect('/select-panel');
        }

        // Se tem acesso a apenas um painel, redirecionar diretamente
        if (count($accessiblePanels) === 1) {
            $panel = array_values($accessiblePanels)[0];
            return redirect($panel['url']);
        }

        // Se não tiver acesso a nada, logout e erro
        Auth::logout();

        return redirect('/login')->withErrors([
            'email' => 'A sua conta não tem permissões para aceder a nenhum painel. Contacte o administrador.',
        ]);
    }

    /**
     * Mostrar página de selecção de painéis.
     */
    public function showPanelSelection()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $accessiblePanels = $this->getAccessiblePanels($user);

        // Se só tem 1 painel, redirecionar
        if (count($accessiblePanels) <= 1) {
            return $this->redirectToPanel($user);
        }

        return view('auth.select-panel', ['panels' => $accessiblePanels, 'user' => $user]);
    }
}
