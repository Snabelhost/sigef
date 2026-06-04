<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static array $auditReferenceCache = [];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão de Acesso';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Utilizador';
    protected static ?string $pluralModelLabel = 'Utilizadores';
    protected static ?string $navigationLabel = 'Utilizadores';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'institution']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::userFormSchema());
    }

    protected static function userFormSchema(): array
    {
        return [
                \Filament\Schemas\Components\Section::make('Dados Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(191),
                    ])->columns(3)->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Segurança')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Palavra-passe')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Palavra-passe')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->requiredWith('password')
                            ->maxLength(191),
                    ])->columns(2)->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Permissões')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição/Escola')
                            ->relationship('institution', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Seleccione a escola para utilizadores do painel Escola')
                            ->columnSpan(1),
                        Forms\Components\Select::make('roles')
                            ->label('Papéis (Roles)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Conta Activa')
                            ->default(true)
                            ->required()
                            ->inline(false),
                    ])->columns(2)->columnSpanFull(),
            ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Papéis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'escola_admin' => 'success',
                        'dpq_admin' => 'info',
                        'comando_admin' => 'primary',
                        'panel_user' => 'gray',
                        'escola_user' => 'success',
                        'dpq_user' => 'info',
                        'comando_user' => 'primary',
                        default => 'gray',
                    })
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth(\Filament\Support\Enums\Width::SixExtraLarge)
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!empty($data['password'])) {
                            $data['_plain_password'] = $data['password'];
                        }
                        return $data;
                    })
                    ->after(function (User $record, array $data) {
                        $plainPassword = $data['_plain_password'] ?? null;
                        if (!empty($plainPassword)) {
                            try {
                                $record->notify(new \App\Notifications\UserCredentialsNotification($plainPassword, true));
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('Falha ao enviar credenciais: ' . $e->getMessage());
                            }
                        }
                    })
                    ->label('Novo Utilizador'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn(User $record): string => 'Visualizar Utilizador - ' . $record->name)
                        ->modalDescription('Dados do utilizador em modo de visualização.')
                        ->modalWidth(\Filament\Support\Enums\Width::SixExtraLarge)
                        ->schema(static::userFormSchema())
                        ->mutateRecordDataUsing(function (array $data, User $record): array {
                            $record->loadMissing('roles');

                            $data['roles'] = $record->roles->pluck('id')->all();
                            $data['password'] = null;
                            $data['password_confirmation'] = null;

                            return $data;
                        })
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\Action::make('audit')
                        ->label('Auditoria')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->modalHeading(fn(User $record) => 'Auditoria - ' . $record->name)
                        ->modalDescription('Consulte as actividades registadas para este utilizador.')
                        ->modalWidth(\Filament\Support\Enums\Width::SixExtraLarge)
                        ->modalContent(fn(User $record) => view('admin.users.audit-modal', [
                            'user' => $record,
                            'activities' => static::getUserAuditEntries($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\Action::make('verAtividades')
                        ->hidden()
                        ->label('Atividades')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->modalHeading(fn(User $record) => 'Atividades - ' . $record->name)
                        ->modalWidth('5xl')
                        ->form(function (User $record) {
                            $activities = \App\Models\ActivityLog::where('user_id', $record->id)
                                ->orderBy('created_at', 'desc')
                                ->limit(30)
                                ->get();

                            $loginCount = $activities->where('action', 'login')->count();
                            $createCount = $activities->where('action', 'create')->count();
                            $updateCount = $activities->where('action', 'update')->count();
                            $deleteCount = $activities->where('action', 'delete')->count();

                            $schema = [
                                \Filament\Schemas\Components\Section::make('Resumo do Utilizador')
                                    ->schema([
                                        Forms\Components\Placeholder::make('user_info')
                                            ->label('Utilizador')
                                            ->content($record->name . ' (' . $record->email . ')'),
                                        Forms\Components\Placeholder::make('session_status')
                                            ->label('Estado da Sessão')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                $record->current_session_id
                                                    ? '<span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400"><span class="w-2 h-2 rounded-full bg-success-500 animate-pulse"></span> Online</span>'
                                                    : '<span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Offline</span>'
                                            )),
                                        Forms\Components\Placeholder::make('last_login')
                                            ->label('Último Login')
                                            ->content($record->last_login_at
                                                ? \Carbon\Carbon::parse($record->last_login_at)->format('d/m/Y H:i') . ' (IP: ' . ($record->last_login_ip ?? 'N/A') . ')'
                                                : 'Nunca fez login'),
                                    ])->columns(3),

                                \Filament\Schemas\Components\Section::make('Estatísticas')
                                    ->schema([
                                        Forms\Components\Placeholder::make('stat_login')
                                            ->label('Logins')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-full bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400">' . $loginCount . '</span>'
                                            )),
                                        Forms\Components\Placeholder::make('stat_create')
                                            ->label('Criações')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">' . $createCount . '</span>'
                                            )),
                                        Forms\Components\Placeholder::make('stat_update')
                                            ->label('Edições')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-full bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">' . $updateCount . '</span>'
                                            )),
                                        Forms\Components\Placeholder::make('stat_delete')
                                            ->label('Exclusões')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-full bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400">' . $deleteCount . '</span>'
                                            )),
                                    ])->columns(4),
                            ];

                            // Adicionar atividades como placeholders
                            if ($activities->isEmpty()) {
                                $schema[] = Forms\Components\Placeholder::make('no_activities')
                                    ->label('')
                                    ->content('Nenhuma atividade registada para este utilizador.')
                                    ->columnSpanFull();
                            } else {
                                $activityItems = [];
                                foreach ($activities as $index => $activity) {
                                    // Badges de texto compactos para ações
                                    $actionBadge = match ($activity->action) {
                                        'login' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400">Login</span>',
                                        'logout' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400">Logout</span>',
                                        'create' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">Criar</span>',
                                        'update' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">Editar</span>',
                                        'delete' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400">Excluir</span>',
                                        'view' => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400">Ver</span>',
                                        default => '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">' . ucfirst($activity->action) . '</span>',
                                    };

                                    // Labels de texto para dispositivos
                                    $deviceLabel = match ($activity->device_type) {
                                        'mobile' => '📱 Mobile',
                                        'tablet' => '📱 Tablet',
                                        default => '💻 Desktop',
                                    };

                                    $content = new \Illuminate\Support\HtmlString(sprintf(
                                        '<div class="flex items-center gap-2 flex-wrap text-sm">%s <span class="text-gray-500">•</span> <span class="text-gray-600 dark:text-gray-400">%s</span> <span class="text-gray-500">•</span> <span class="text-gray-500">%s</span> <span class="text-gray-500">•</span> <span class="text-gray-500">%s/%s</span> <span class="text-gray-500">•</span> <span class="font-mono text-xs text-gray-400">%s</span></div>',
                                        $actionBadge,
                                        $activity->module ?? '-',
                                        $deviceLabel,
                                        $activity->browser ?? 'N/A',
                                        $activity->platform ?? 'N/A',
                                        $activity->ip_address ?? 'N/A'
                                    ));

                                    $activityItems[] = Forms\Components\Placeholder::make('activity_' . $index)
                                        ->label($activity->created_at->format('d/m/Y H:i:s'))
                                        ->content($content);
                                }

                                $schema[] = \Filament\Schemas\Components\Section::make('Últimas ' . $activities->count() . ' Atividades')
                                    ->schema($activityItems)
                                    ->collapsible();
                            }

                            // Link para ver todas
                            $schema[] = Forms\Components\Placeholder::make('ver_todas')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<a href="#" 
                                    class="text-primary-600 hover:text-primary-500 font-medium" 
                                    target="_blank">
                                    Ver todas as atividades →
                                </a>'
                                ))
                                ->columnSpanFull();

                            return $schema;
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->mutateFormDataUsing(function (array $data): array {
                            if (!empty($data['password'])) {
                                $data['_plain_password'] = $data['password'];
                            }
                            return $data;
                        })
                        ->after(function (User $record, array $data) {
                            $plainPassword = $data['_plain_password'] ?? null;
                            if (!empty($plainPassword)) {
                                try {
                                    $record->notify(new \App\Notifications\UserCredentialsNotification($plainPassword, false));
                                } catch (\Throwable $e) {
                                    \Illuminate\Support\Facades\Log::warning('Falha ao enviar credenciais: ' . $e->getMessage());
                                }
                            }
                        })
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getUserAuditEntries(User $record): \Illuminate\Support\Collection
    {
        $activityEntries = \App\Models\ActivityLog::query()
            ->with('user')
            ->where('user_id', $record->getKey())
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->map(fn(\App\Models\ActivityLog $activity): object => (object) [
                'created_at' => $activity->created_at,
                'event_label' => static::activityEventLabel($activity),
                'description' => static::activityDescription($activity),
                'actor' => $activity->user,
                'method' => $activity->method,
                'path' => $activity->url,
                'ip_address' => $activity->ip_address,
            ]);

        $userMorphs = array_unique([User::class, (new User())->getMorphClass()]);

        $auditEntries = \OwenIt\Auditing\Models\Audit::query()
            ->with('user')
            ->where(function (Builder $query) use ($record, $userMorphs): void {
                $query
                    ->where('user_id', $record->getKey())
                    ->orWhere(function (Builder $query) use ($record, $userMorphs): void {
                        $query
                            ->whereIn('auditable_type', $userMorphs)
                            ->where('auditable_id', $record->getKey());
                    });
            })
            ->latest('created_at')
            ->limit(400)
            ->get()
            ->reject(fn($audit): bool => static::isTechnicalSessionAudit($audit))
            ->map(fn($audit): object => (object) [
                'created_at' => $audit->created_at,
                'event_label' => static::auditEventLabel($audit),
                'description' => static::auditDescription($audit),
                'actor' => $audit->user,
                'method' => '-',
                'path' => $audit->url,
                'ip_address' => $audit->ip_address,
            ]);

        return $activityEntries
            ->concat($auditEntries)
            ->sortByDesc(fn(object $entry): int => $entry->created_at?->timestamp ?? 0)
            ->unique(fn(object $entry): string => implode('|', [
                $entry->created_at?->format('Y-m-d H:i:s') ?? '',
                $entry->event_label,
                $entry->description,
                $entry->actor?->getKey() ?? '',
                $entry->method ?? '',
                $entry->path ?? '',
                $entry->ip_address ?? '',
            ]))
            ->take(200)
            ->values();
    }

    protected static function activityEventLabel(\App\Models\ActivityLog|string $activity): string
    {
        $action = is_string($activity) ? $activity : (string) $activity->action;

        return match ($action) {
            'login' => 'Entrada no sistema',
            'logout' => 'Saida do sistema',
            'create' => 'Registo criado',
            'update' => 'Registo atualizado',
            'delete' => 'Registo eliminado',
            'view' => 'Consulta',
            default => str($action)->replace(['_', '.', '-'], ' ')->title()->toString(),
        };
    }

    protected static function activityDescription(\App\Models\ActivityLog|string $activity): string
    {
        if (is_string($activity)) {
            return match ($activity) {
                'login' => 'Utilizador iniciou sessao no sistema',
                'logout' => 'Utilizador encerrou sessao no sistema',
                default => static::activityEventLabel($activity),
            };
        }

        if (filled($activity->description)) {
            return (string) $activity->description;
        }

        $changes = static::changesSummary(
            static::normalizeAuditValues($activity->old_values),
            static::normalizeAuditValues($activity->new_values),
            (string) $activity->model_type,
            (string) $activity->action
        );

        if ($changes) {
            return $changes;
        }

        return match ((string) $activity->action) {
            'login' => 'Entrou no sistema',
            'logout' => 'Saiu do sistema',
            default => static::activityEventLabel($activity),
        };
    }

    protected static function auditEventLabel($audit): string
    {
        if (is_string($audit)) {
            return match ($audit) {
                'created' => 'Registo criado',
                'updated' => 'Registo atualizado',
                'deleted' => 'Registo eliminado',
                'restored' => 'Registo restaurado',
                default => str($audit)->replace(['_', '.', '-'], ' ')->title()->toString(),
            };
        }

        $modelLabel = static::auditModelLabel((string) $audit->auditable_type);

        return match ((string) $audit->event) {
            'created' => "{$modelLabel} criado",
            'updated' => "{$modelLabel} atualizado",
            'deleted' => "{$modelLabel} eliminado",
            'restored' => "{$modelLabel} restaurado",
            default => $modelLabel . ' ' . str((string) $audit->event)->replace(['_', '.', '-'], ' ')->lower()->toString(),
        };
    }

    protected static function auditDescription($audit): string
    {
        $oldValues = static::normalizeAuditValues($audit->old_values);
        $newValues = static::normalizeAuditValues($audit->new_values);
        $modelType = (string) $audit->auditable_type;
        $modelLabel = static::auditModelLabel($modelType);
        $recordName = static::auditRecordName($audit, $oldValues, $newValues);

        $subject = $modelLabel . ($recordName ? ' "' . $recordName . '"' : ' #' . $audit->auditable_id);
        $event = (string) $audit->event;
        $changes = static::changesSummary($oldValues, $newValues, $modelType, $event);

        $description = match ($event) {
            'created' => "Criou {$subject}",
            'updated' => "Atualizou {$subject}",
            'deleted' => "Eliminou {$subject}",
            'restored' => "Restaurou {$subject}",
            default => static::auditEventLabel($audit) . ' ' . $subject,
        };

        return $changes ? "{$description}: {$changes}" : $description;
    }

    protected static function isTechnicalSessionAudit($audit): bool
    {
        if (!in_array((string) $audit->auditable_type, [User::class, (new User())->getMorphClass()], true)) {
            return false;
        }

        $keys = static::changedAuditKeys(
            static::normalizeAuditValues($audit->old_values),
            static::normalizeAuditValues($audit->new_values)
        );

        if ($keys === []) {
            return false;
        }

        return empty(array_diff($keys, [
            'current_session_id',
            'last_login_at',
            'last_login_ip',
            'remember_token',
        ]));
    }

    protected static function normalizeAuditValues($values): array
    {
        if ($values instanceof \Illuminate\Support\Collection) {
            return $values->toArray();
        }

        if (is_array($values)) {
            return $values;
        }

        if (is_object($values) && method_exists($values, 'toArray')) {
            return $values->toArray();
        }

        if (is_string($values) && trim($values) !== '') {
            $decoded = json_decode($values, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected static function changedAuditKeys(array $oldValues, array $newValues): array
    {
        return array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
    }

    protected static function changesSummary(array $oldValues, array $newValues, ?string $modelType = null, ?string $event = null): string
    {
        $parts = [];
        $keys = static::changedAuditKeys($oldValues, $newValues);

        foreach ($keys as $key) {
            if (in_array($key, ['id', 'current_session_id', 'last_login_at', 'last_login_ip', 'remember_token'], true)) {
                continue;
            }

            if ($key === 'password') {
                $parts[] = 'Palavra-passe: ' . ($event === 'created' ? 'definida' : 'alterada');
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($event === 'created') {
                if ($newValue === null || $newValue === '') {
                    continue;
                }

                $parts[] = static::auditFieldLabel($key) . ': ' . static::formatAuditValue($key, $newValue, $modelType);
                continue;
            }

            if ($event === 'deleted') {
                if ($oldValue === null || $oldValue === '') {
                    continue;
                }

                $parts[] = static::auditFieldLabel($key) . ': ' . static::formatAuditValue($key, $oldValue, $modelType);
                continue;
            }

            if (static::valuesAreEqual($oldValue, $newValue)) {
                continue;
            }

            $parts[] = static::auditFieldLabel($key) . ': '
                . static::formatAuditValue($key, $oldValue, $modelType)
                . ' -> '
                . static::formatAuditValue($key, $newValue, $modelType);
        }

        if ($parts === []) {
            return '';
        }

        $extraCount = max(count($parts) - 6, 0);
        $parts = array_slice($parts, 0, 6);

        if ($extraCount > 0) {
            $parts[] = "+{$extraCount} campo(s)";
        }

        return implode('; ', $parts);
    }

    protected static function valuesAreEqual($oldValue, $newValue): bool
    {
        return json_encode($oldValue, JSON_UNESCAPED_UNICODE) === json_encode($newValue, JSON_UNESCAPED_UNICODE);
    }

    protected static function auditModelLabel(?string $modelType): string
    {
        return [
            'App\\Models\\User' => 'Utilizador',
            'App\\Models\\Candidate' => 'Alistado',
            'App\\Models\\Student' => 'Formando',
            'App\\Models\\Trainer' => 'Formador',
            'App\\Models\\Institution' => 'Instituicao',
            'App\\Models\\InstitutionType' => 'Tipo de instituicao',
            'App\\Models\\Course' => 'Curso',
            'App\\Models\\CourseMap' => 'Mapa de curso',
            'App\\Models\\CoursePlan' => 'Plano de curso',
            'App\\Models\\Subject' => 'Disciplina',
            'App\\Models\\Rank' => 'Patente',
            'App\\Models\\Provenance' => 'Orgao de proveniencia',
            'App\\Models\\RecruitmentType' => 'Tipo de recrutamento',
            'App\\Models\\StudentType' => 'Tipo de aluno',
            'App\\Models\\StudentClass' => 'Turma',
            'App\\Models\\StudentLeave' => 'Ocorrencia',
            'App\\Models\\Evaluation' => 'Avaliacao',
            'App\\Models\\EquipmentAssignment' => 'Atribuicao de equipamento',
            'App\\Models\\Document' => 'Documento',
        ][$modelType] ?? (class_basename((string) $modelType) ?: 'Registo');
    }

    protected static function auditFieldLabel(string $field): string
    {
        return [
            'name' => 'Nome',
            'full_name' => 'Nome completo',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'password' => 'Palavra-passe',
            'institution_id' => 'Instituicao',
            'institution_type_id' => 'Tipo de instituicao',
            'is_active' => 'Estado',
            'status' => 'Estado',
            'student_type' => 'Tipo',
            'student_type_id' => 'Tipo de aluno',
            'id_number' => 'N. BI',
            'bi_number' => 'N. BI',
            'bilhete' => 'N. BI',
            'nuri' => 'NURI',
            'student_number' => 'N. de aluno',
            'candidate_id' => 'Alistado',
            'provenance_id' => 'Proveniencia',
            'rank_id' => 'Patente',
            'current_rank_id' => 'Patente',
            'recruitment_type_id' => 'Tipo de recrutamento',
            'course_id' => 'Curso',
            'course_map_id' => 'Mapa de curso',
            'course_plan_id' => 'Plano de curso',
            'academic_year_id' => 'Ano lectivo',
            'subject_id' => 'Disciplina',
            'class_id' => 'Turma',
            'student_class_id' => 'Turma',
            'title' => 'Titulo',
            'description' => 'Descricao',
            'reference_number' => 'N. de referencia',
        ][$field] ?? str($field)->replace('_', ' ')->title()->toString();
    }

    protected static function auditRecordName($audit, array $oldValues, array $newValues): ?string
    {
        foreach ([$newValues, $oldValues] as $values) {
            foreach (['name', 'full_name', 'title', 'email', 'student_number', 'id_number', 'nuri', 'reference_number'] as $field) {
                if (!empty($values[$field]) && is_scalar($values[$field])) {
                    return static::formatAuditValue($field, $values[$field], (string) $audit->auditable_type);
                }
            }
        }

        try {
            if (is_string($audit->auditable_type) && class_exists($audit->auditable_type)) {
                $auditable = $audit->auditable_type::find($audit->auditable_id);
                return $auditable?->name
                    ?? $auditable?->full_name
                    ?? $auditable?->title
                    ?? $auditable?->email
                    ?? $auditable?->student_number
                    ?? null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected static function formatAuditValue(string $field, $value, ?string $modelType = null): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $field === 'is_active'
                ? ($value ? 'Activo' : 'Inactivo')
                : ($value ? 'Sim' : 'Nao');
        }

        if (is_array($value)) {
            return str(json_encode($value, JSON_UNESCAPED_UNICODE))->limit(90)->toString();
        }

        $resolved = static::resolveAuditReference($field, $value);

        if ($resolved !== null) {
            return $resolved;
        }

        if (is_string($value) && preg_match('/(_at|_date|date)$/', $field)) {
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i');
            } catch (\Throwable) {
                //
            }
        }

        return str((string) $value)->limit(90)->toString();
    }

    protected static function resolveAuditReference(string $field, $value): ?string
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $map = [
            'institution_id' => ['App\\Models\\Institution', ['name']],
            'institution_type_id' => ['App\\Models\\InstitutionType', ['name']],
            'candidate_id' => ['App\\Models\\Candidate', ['full_name', 'id_number']],
            'provenance_id' => ['App\\Models\\Provenance', ['name', 'acronym']],
            'rank_id' => ['App\\Models\\Rank', ['name']],
            'current_rank_id' => ['App\\Models\\Rank', ['name']],
            'recruitment_type_id' => ['App\\Models\\RecruitmentType', ['name']],
            'student_type_id' => ['App\\Models\\StudentType', ['name']],
            'course_id' => ['App\\Models\\Course', ['name']],
            'course_map_id' => ['App\\Models\\CourseMap', ['name']],
            'course_plan_id' => ['App\\Models\\CoursePlan', ['name']],
            'academic_year_id' => ['App\\Models\\AcademicYear', ['name', 'year']],
            'subject_id' => ['App\\Models\\Subject', ['name']],
            'class_id' => ['App\\Models\\StudentClass', ['name']],
            'student_class_id' => ['App\\Models\\StudentClass', ['name']],
        ];

        if (!isset($map[$field])) {
            return null;
        }

        [$class, $columns] = $map[$field];
        $cacheKey = $field . ':' . $value;

        if (array_key_exists($cacheKey, static::$auditReferenceCache)) {
            return static::$auditReferenceCache[$cacheKey];
        }

        try {
            if (!class_exists($class)) {
                return static::$auditReferenceCache[$cacheKey] = '#' . $value;
            }

            $record = $class::find($value);

            if (!$record) {
                return static::$auditReferenceCache[$cacheKey] = '#' . $value;
            }

            $label = collect($columns)
                ->map(fn(string $column) => $record->{$column} ?? null)
                ->filter()
                ->implode(' - ');

            return static::$auditReferenceCache[$cacheKey] = ($label ?: '#' . $value);
        } catch (\Throwable) {
            return static::$auditReferenceCache[$cacheKey] = '#' . $value;
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:User') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
