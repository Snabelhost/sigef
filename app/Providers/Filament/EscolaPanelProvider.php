<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;

class EscolaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('escola')
            ->path('escola')
            ->login(false) // Desabilitar login do painel - usar /login unificado
            ->brandLogo(fn() => view('filament.brand-logo'))
            ->brandLogoHeight('50px')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce(500)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Poll for new notifications every 30 seconds
            ->defaultAvatarProvider(\App\Providers\CustomAvatarProvider::class)
            ->colors([
                'primary' => [
                    50 => '236, 239, 247',   // muito claro
                    100 => '200, 210, 235',
                    200 => '150, 170, 210',
                    300 => '100, 130, 175',
                    400 => '50, 80, 140',
                    500 => '4, 28, 79',      // #041c4f base
                    600 => '4, 28, 79',      // #041c4f
                    700 => '3, 22, 65',
                    800 => '2, 18, 50',
                    900 => '2, 14, 40',
                    950 => '1, 10, 30',
                ],
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    $themeVersion = @filemtime(public_path('css/sigef-theme.css')) ?: time();
                    $layoutVersion = @filemtime(public_path('js/sigef-layout-stability.js')) ?: time();

                    return '
                    <link rel="stylesheet" href="/css/sigef-theme.css?v=' . $themeVersion . '">
                    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
                    <link rel="icon" type="image/png" href="/favicon.png">
                    <link rel="shortcut icon" href="/favicon.png">
                    <link rel="apple-touch-icon" href="/favicon.png">
                    <script src="/js/favicon-inject.js"></script>
                    <script src="/js/sigef-layout-stability.js?v=' . $layoutVersion . '" defer></script>
                    <script>
                        // Remover botão nativo de colapso do Filament
                        document.addEventListener("DOMContentLoaded", function() {
                            function removeNativeCollapseButton() {
                                // Procurar botões na sidebar header que não são nosso botão customizado
                                const sidebarHeader = document.querySelector(".fi-sidebar-header");
                                if (sidebarHeader) {
                                    const buttons = sidebarHeader.querySelectorAll("button:not(.brand-logo-btn)");
                                    buttons.forEach(function(btn) {
                                        if (!btn.classList.contains("brand-logo-btn")) {
                                            btn.style.display = "none";
                                            btn.remove();
                                        }
                                    });
                                }
                            }
                            removeNativeCollapseButton();
                            setTimeout(removeNativeCollapseButton, 100);
                            setTimeout(removeNativeCollapseButton, 500);
                            setTimeout(removeNativeCollapseButton, 1000);
                        });
                    </script>
                ';
                }
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn() => view('filament.header')
            )
            ->tenant(\App\Models\Institution::class)
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Currículo'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestão Escolar'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Instituições'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Documentos'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Relatórios'),
            ])
            ->discoverResources(in: app_path('Filament/Escola/Resources'), for: 'App\\Filament\\Escola\\Resources')
            ->resources([])
            ->discoverPages(in: app_path('Filament/Escola/Pages'), for: 'App\\Filament\\Escola\\Pages')
            ->pages([
                \App\Filament\Escola\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Escola/Widgets'), for: 'App\\Filament\\Escola\\Widgets')
            ->widgets([
                \App\Filament\Escola\Widgets\EscolaStatsOverview::class,
                \App\Filament\Escola\Widgets\EscolaCandidateStatusChart::class,
                \App\Filament\Escola\Widgets\EscolaStudentStatusChart::class,
                \App\Filament\Escola\Widgets\EscolaStudentManagement::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                // FilamentShield desabilitado - incompatível com multi-tenancy
            ])
            ->authMiddleware([
                Authenticate::class, // Middleware customizado que redireciona para /login
                \App\Http\Middleware\RefreshUserPermissions::class, // Atualiza permissões a cada request
                \App\Http\Middleware\SingleSessionMiddleware::class, // Sessão única
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Alterar Palavra-passe')
                    ->icon('heroicon-o-key')
                    ->url('javascript:void(0)'),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn() => '<div id="change-password-portal">' . Blade::render('@livewire(\'change-password-modal\')') . '</div>' .
                    '<script>
                        document.addEventListener("click", function(e) {
                            var el = e.target.closest("a");
                            if (el && el.textContent.trim().includes("Alterar Palavra-passe")) {
                                e.preventDefault();
                                e.stopPropagation();
                                window.dispatchEvent(new CustomEvent("open-change-password"));
                            }
                        }, true);
                        (function movePortal() {
                            var portal = document.getElementById("change-password-portal");
                            if (portal && portal.parentElement !== document.body) {
                                document.body.appendChild(portal);
                            } else {
                                setTimeout(movePortal, 200);
                            }
                        })();
                    </script>'
            );
    }
}
