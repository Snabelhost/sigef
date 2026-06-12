<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProfessoresPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('professores')
            ->path('professores')
            ->login(false)
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('50px')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->defaultAvatarProvider(\App\Providers\CustomAvatarProvider::class)
            ->colors([
                'primary' => [
                    50 => '236, 239, 247',
                    100 => '200, 210, 235',
                    200 => '150, 170, 210',
                    300 => '100, 130, 175',
                    400 => '50, 80, 140',
                    500 => '4, 28, 79',
                    600 => '4, 28, 79',
                    700 => '3, 22, 65',
                    800 => '2, 18, 50',
                    900 => '2, 14, 40',
                    950 => '1, 10, 30',
                ],
                ...\App\Support\FilamentStatusColors::palettes(),
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
                ';
                }
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn () => view('filament.header')
            )
            ->pages([
                \App\Filament\Professores\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Professores/Widgets'), for: 'App\\Filament\\Professores\\Widgets')
            ->widgets([
                \App\Filament\Professores\Widgets\ProfessorOverview::class,
                \App\Filament\Professores\Widgets\ProfessorAssignments::class,
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
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\RefreshUserPermissions::class,
                \App\Http\Middleware\SingleSessionMiddleware::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Alterar Palavra-passe')
                    ->icon('heroicon-o-key')
                    ->url('javascript:void(0)'),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => '<div id="change-password-portal">' . Blade::render('@livewire(\'change-password-modal\')') . '</div>'
            );
    }
}
