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

class EscolaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('escola')
            ->path('escola')
            ->login(false)
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('50px')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(\App\Providers\NavigationSearchProvider::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce(500)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
                    $photoVersion = @filemtime(public_path('js/sigef-photo-upload.js')) ?: time();

                    return '
                    <link rel="stylesheet" href="/css/sigef-theme.css?v=' . $themeVersion . '">
                    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
                    <link rel="icon" type="image/png" href="/favicon.png">
                    <link rel="shortcut icon" href="/favicon.png">
                    <link rel="apple-touch-icon" href="/favicon.png">
                    <script src="/js/favicon-inject.js"></script>
                    <script src="/js/sigef-layout-stability.js?v=' . $layoutVersion . '" defer></script>
                    <script src="/js/sigef-photo-upload.js?v=' . $photoVersion . '" defer></script>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            function removeNativeCollapseButton() {
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
                fn () => view('filament.header')
            )
            ->tenant(\App\Models\Institution::class)
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Currículo'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestão do Centro'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Recursos Humanos'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Instituições'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Comunicação'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Relatórios'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Configurações'),
            ])
            ->resources([
                \App\Filament\Escola\Resources\AcademicYearResource::class,
                \App\Filament\Escola\Resources\CandidateResource::class,
                \App\Filament\Resources\CandidateTransferHistories\CandidateTransferHistoryResource::class,
                \App\Filament\Resources\CardTemplateResource::class,
                \App\Filament\Escola\Resources\CourseMapResource::class,
                \App\Filament\Escola\Resources\CoursePhaseResource::class,
                \App\Filament\Escola\Resources\CoursePlanResource::class,
                \App\Filament\Escola\Resources\CourseResource::class,
                \App\Filament\Escola\Resources\DocumentResource::class,
                \App\Filament\Escola\Resources\EffectiveResource::class,
                \App\Filament\Escola\Resources\EquipmentAssignmentResource::class,
                \App\Filament\Escola\Resources\EvaluationResource::class,
                \App\Filament\Escola\Resources\InstitutionResource::class,
                \App\Filament\Resources\InstitutionTypeResource::class,
                \App\Filament\Escola\Resources\PautaGeralResource::class,
                \App\Filament\Escola\Resources\PautaResource::class,
                \App\Filament\Resources\ProvenanceResource::class,
                \App\Filament\Resources\RankResource::class,
                \App\Filament\Escola\Resources\StudentClassEnrollmentResource::class,
                \App\Filament\Escola\Resources\StudentClassResource::class,
                \App\Filament\Escola\Resources\StudentLeaveResource::class,
                \App\Filament\Resources\StudentTypeResource::class,
                \App\Filament\Escola\Resources\SubjectResource::class,
                \App\Filament\Escola\Resources\TrainerClassAssignmentResource::class,
                \App\Filament\Escola\Resources\TrainerResource::class,
            ])
            ->pages([
                \App\Filament\Escola\Pages\Dashboard::class,
                \App\Filament\Escola\Pages\AttendanceManagement::class,
                \App\Filament\Pages\InstitutionReportSettings::class,
                \App\Filament\Escola\Pages\Relatorios::class,
                \App\Filament\Escola\Pages\TransferHistory::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\CandidatesByProvinceChart::class,
                \App\Filament\Widgets\CandidateStatusChart::class,
                \App\Filament\Widgets\StudentStatusChart::class,
                \App\Filament\Widgets\StudentsByCourseChart::class,
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
                \Tapp\FilamentAuditing\FilamentAuditingPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\RefreshUserPermissions::class,
                \App\Http\Middleware\SingleSessionMiddleware::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Mudar de painel')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn (): string => route('select-panel'))
                    ->visible(fn (): bool => auth()->check() && count(auth()->user()->accessiblePanels()) > 1),
                \Filament\Navigation\MenuItem::make()
                    ->label('Alterar Palavra-passe')
                    ->icon('heroicon-o-key')
                    ->url('javascript:void(0)'),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => '<div id="change-password-portal">' . Blade::render('@livewire(\'change-password-modal\')') . '</div>' .
                    '<div id="stats-detail-portal">' . Blade::render('@livewire(\'stats-detail-modal\')') . '</div>' .
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
                            var passwordPortal = document.getElementById("change-password-portal");
                            if (passwordPortal && passwordPortal.parentElement !== document.body) {
                                document.body.appendChild(passwordPortal);
                            }

                            var statsPortal = document.getElementById("stats-detail-portal");
                            if (statsPortal && statsPortal.parentElement !== document.body) {
                                document.body.appendChild(statsPortal);
                            }

                            if (!passwordPortal || !statsPortal) {
                                setTimeout(movePortal, 200);
                            }
                        })();
                    </script>'
            );
    }
}
