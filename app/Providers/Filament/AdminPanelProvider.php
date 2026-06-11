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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(false) // Desabilitar login do painel - usar /login unificado
            ->brandLogo(fn() => view('filament.brand-logo'))
            ->brandLogoHeight('50px')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(\App\Providers\NavigationSearchProvider::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce(500)
            ->databaseNotifications()
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
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestão de Acesso'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Currículo'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestão Escolar'),
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
                \App\Filament\Resources\AcademicYearResource::class,
                \App\Filament\Resources\CandidateResource::class,
                \App\Filament\Resources\CandidateTransferHistories\CandidateTransferHistoryResource::class,
                \App\Filament\Resources\CardTemplateResource::class,
                \App\Filament\Resources\CourseMapResource::class,
                \App\Filament\Resources\CoursePlanResource::class,
                \App\Filament\Resources\CourseResource::class,
                \App\Filament\Resources\DocumentResource::class,
                \App\Filament\Resources\EffectiveResource::class,
                \App\Filament\Resources\EquipmentAssignmentResource::class,
                \App\Filament\Resources\EvaluationResource::class,
                \App\Filament\Resources\InstitutionResource::class,
                \App\Filament\Resources\InstitutionTypeResource::class,
                \App\Filament\Resources\PautaGeralResource::class,
                \App\Filament\Resources\PautaResource::class,
                \App\Filament\Resources\ProvenanceResource::class,
                \App\Filament\Resources\RankResource::class,
                \App\Filament\Resources\Shield\RoleResource::class,
                \App\Filament\Resources\StudentClassEnrollmentResource::class,
                \App\Filament\Resources\StudentClassResource::class,
                \App\Filament\Resources\StudentLeaveResource::class,
                \App\Filament\Resources\StudentTransferHistories\StudentTransferHistoryResource::class,
                \App\Filament\Resources\StudentTypeResource::class,
                \App\Filament\Resources\SubjectResource::class,
                \App\Filament\Resources\TrainerClassAssignmentResource::class,
                \App\Filament\Resources\TrainerResource::class,
                \App\Filament\Resources\UserResource::class,
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\AttendanceManagement::class,
                \App\Filament\Pages\BackupSettings::class,
                \App\Filament\Pages\InstitutionReportSettings::class,
                \App\Filament\Pages\MailSettings::class,
                \App\Filament\Pages\Relatorios::class,
                \App\Filament\Pages\TransferHistory::class,
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
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Tapp\FilamentAuditing\FilamentAuditingPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class, // Middleware customizado que redireciona para /login
                \App\Http\Middleware\RefreshUserPermissions::class, // Atualiza permissões a cada request
                \App\Http\Middleware\SingleSessionMiddleware::class, // Sessão única - invalida login em outros dispositivos
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
                        // Move portal to body root to escape CSS containment
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
