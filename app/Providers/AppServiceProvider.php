<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Listeners\PermissionEventSubscriber;
use App\Observers\RoleObserver;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar LogoutResponse personalizado para redirecionar corretamente ao sair
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(10)->mixedCase()->numbers()->symbols());

        // ============================================================
        // HTTPS AUTOMÁTICO EM PRODUÇÃO
        // ============================================================
        // Força HTTPS em produção para o servidor online.
        // No ambiente local (APP_ENV=local), usa HTTP normalmente.
        // ============================================================
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        // ============================================================
        // SUPER ADMIN BYPASS - Solução definitiva para permissões
        // ============================================================
        // Garante que usuários com role 'super_admin' SEMPRE tenham
        // acesso total a todas as funcionalidades, independentemente
        // das permissões individuais atribuídas na tabela pivot.
        // Isso resolve o problema de permissões faltando permanentemente.
        // ============================================================
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true; // Permite tudo para super_admin
            }
            return null; // Continua verificação normal para outros usuários
        });

        // Registrar Observer para limpar cache de permissões quando roles são atualizadas
        Role::observe(RoleObserver::class);

        // Registrar Event Subscriber para limpar cache quando permissões são alteradas
        Event::subscribe(PermissionEventSubscriber::class);

        // Registrar listener para controle de sessão única (Single Session)
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\UpdateUserSessionOnLogin::class
        );

        // Registrar listener para log de logout
        Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            \App\Listeners\LogUserLogout::class
        );

        // Registrar CSS personalizado do SIGEF em todos os painéis Filament (no HEAD)
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn(): HtmlString => new HtmlString('<link rel="stylesheet" href="/css/sigef-theme.css">')
        );



        // Agrupar ações de linha da tabela em dropdown (⋮) globalmente em todos os painéis
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table->actionsColumnLabel('Ações');
        });

        // Configurar modais de exclusão globalmente
        $this->registerDeleteActionModalDefaults();

        // Registrar ícone customizado de velocímetro
        Blade::component('icon-speedometer', \App\View\Components\IconSpeedometer::class);

        // ============================================================
        // AUTO STORAGE LINK - Cria symlink automaticamente se não existir
        // ============================================================
        // Garante que o symlink public/storage -> storage/app/public
        // está sempre presente em qualquer servidor (local ou produção),
        // resolvendo definitivamente o problema de fotos/ficheiros
        // que não aparecem após deploy.
        // ============================================================
        $storageLinkPath = public_path('storage');
        $storageTargetPath = storage_path('app/public');

        if (
            ! file_exists($storageLinkPath)
            && is_dir($storageTargetPath)
            && is_writable(public_path())
        ) {
            try {
                app()->make('files')->link(
                    $storageTargetPath,
                    $storageLinkPath
                );
            } catch (\Throwable $exception) {
                error_log('SIGEF: could not create the public storage link: '.$exception->getMessage());
            }
        }
    }

    private function registerDeleteActionModalDefaults(): void
    {
        $actionClasses = [
            DeleteAction::class,
            DeleteBulkAction::class,
            ForceDeleteAction::class,
            ForceDeleteBulkAction::class,
            'Filament\\Tables\\Actions\\DeleteAction',
            'Filament\\Tables\\Actions\\DeleteBulkAction',
            'Filament\\Tables\\Actions\\ForceDeleteAction',
            'Filament\\Tables\\Actions\\ForceDeleteBulkAction',
        ];

        foreach ($actionClasses as $actionClass) {
            if (! is_subclass_of($actionClass, Action::class)) {
                continue;
            }

            $actionClass::configureUsing(
                fn (Action $action): Action => $this->configureDeleteActionModal($action),
                isImportant: true,
            );
        }
    }

    private function configureDeleteActionModal(Action $action): Action
    {
        return $action
            ->extraModalWindowAttributes(['class' => 'sigef-delete-modal'], merge: true)
            ->modalIconColor('primary')
            ->modalSubmitActionLabel('Excluir')
            ->modalCancelActionLabel('Cancelar')
            ->modalCancelAction(fn (Action $action): Action => $action
                ->label('Cancelar')
                ->icon('heroicon-o-x-mark')
                ->color('primary'))
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->label('Excluir')
                ->icon('heroicon-o-trash')
                ->color('danger'));
    }
}
