<?php

namespace App\Filament\Resources\Shield;

use App\Filament\Resources\Shield\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as BaseRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action as TableAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleResource extends BaseRoleResource
{
    protected static ?string $modelLabel = 'Acesso';

    protected static ?string $pluralModelLabel = 'Acessos';

    protected static ?string $navigationLabel = 'Acessos';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?array $permissionGroupsCache = null;

    protected const PERMISSION_TYPES = [
        'panels' => 'Painéis',
        'resources' => 'Recursos',
        'pages' => 'Páginas',
        'reports' => 'Relatórios',
        'dashboard' => 'Painel',
        'legacy' => 'Gerais',
    ];

    public static function getModelLabel(): string
    {
        return 'Acesso';
    }

    public static function getTitleCaseModelLabel(): string
    {
        return 'Acesso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Acessos';
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Acessos';
    }

    public static function getNavigationLabel(): string
    {
        return 'Acessos';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function canGloballySearch(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {
        return static::roleLabel((string) $record->getAttribute('name'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do perfil')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Perfil')
                            ->placeholder('Ex: secretaria')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'roles',
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('guard_name', static::guardName()),
                            )
                            ->helperText(fn (?string $state): string => filled($state)
                                ? 'Apresentado como: ' . static::roleLabel((string) $state)
                                : 'Identificador interno do perfil.'
                            )
                            ->live(onBlur: true),

                        TextInput::make('guard_name')
                            ->label('Área interna')
                            ->default(static::guardName())
                            ->disabled()
                            ->dehydrated()
                            ->hidden()
                            ->required(),

                        Toggle::make('select_all_permissions')
                            ->label('Selecionar todos')
                            ->helperText('Habilitar todas as permissões para este perfil.')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (?bool $state, callable $set): mixed => static::setAllPermissionStates($set, (bool) $state)),

                        TextInput::make('permissions_search')
                            ->label('Pesquisar acessos')
                            ->placeholder('Pesquisar por listagem, página ou ação')
                            ->helperText('Filtra todos os grupos de permissão abaixo.')
                            ->live(debounce: 300)
                            ->dehydrated(false)
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'data-sigef-permission-search' => 'true',
                            ]),
                    ]),

                Tabs::make('Grupos de acesso')
                    ->columnSpanFull()
                    ->tabs(static::permissionTabs()),
            ]);
    }

    /**
     * @return array<int, Tab>
     */
    protected static function permissionTabs(): array
    {
        return [
            Tab::make('Painéis')
                ->badge(static::permissionTabBadge('panels'))
                ->schema(static::permissionGroupSections('panels')),
            Tab::make('Recursos')
                ->badge(static::permissionTabBadge('resources'))
                ->schema(static::permissionGroupSections('resources')),
            Tab::make('Páginas')
                ->badge(static::permissionTabBadge('pages'))
                ->schema(static::permissionGroupSections('pages')),
            Tab::make('Relatórios')
                ->badge(static::permissionTabBadge('reports'))
                ->schema(static::permissionGroupSections('reports')),
            Tab::make('Painel')
                ->badge(static::permissionTabBadge('dashboard'))
                ->schema(static::permissionGroupSections('dashboard')),
            Tab::make('Gerais')
                ->badge(static::permissionTabBadge('legacy'))
                ->schema(static::permissionGroupSections('legacy')),
        ];
    }

    protected static function permissionTabBadge(string $type): int
    {
        $groups = static::permissionGroups($type);

        if ($type === 'resources') {
            return count($groups);
        }

        return collect($groups)->sum(fn (array $group): int => count($group['permissions']));
    }

    /**
     * @return array<int, Section>
     */
    protected static function permissionGroupSections(string $type): array
    {
        $groups = static::permissionGroups($type);

        if ($groups === []) {
            return [
                Section::make(static::emptyPermissionGroupLabel($type))
                    ->description('Nenhuma permissão encontrada nesta categoria.')
                    ->columnSpanFull()
                    ->schema([]),
            ];
        }

        return array_map(
            fn (array $group): Section => static::permissionGroupSection($type, $group),
            $groups,
        );
    }

    protected static function permissionGroupSection(string $type, array $group): Section
    {
        return Section::make($group['label'])
            ->description($group['description'])
            ->columnSpanFull()
            ->collapsible()
            ->extraAttributes([
                'data-sigef-permission-group' => 'true',
                'data-sigef-permission-search-text' => static::permissionGroupSearchText($group),
            ])
            ->visible(fn (callable $get): bool => static::permissionGroupMatchesSearch($group, $get('permissions_search')))
            ->schema([
                CheckboxList::make("permission_groups.{$type}.{$group['key']}")
                    ->hiddenLabel()
                    ->options(collect($group['permissions'])
                        ->mapWithKeys(fn (array $permission, string $permissionKey): array => [
                            $permissionKey => $permission['label'],
                        ])
                        ->all())
                    ->searchable()
                    ->bulkToggleable()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->dehydrated(false)
                    ->default([]),
            ])
            ->columns(1);
    }

    protected static function permissionGroupSearchText(array $group): string
    {
        $terms = [
            $group['label'] ?? '',
            $group['description'] ?? '',
        ];

        foreach (($group['permissions'] ?? []) as $permission) {
            $terms[] = $permission['label'] ?? '';
            $terms[] = $permission['name'] ?? '';
        }

        return static::normalizePermissionSearch(implode(' ', array_filter($terms)));
    }

    protected static function permissionGroupMatchesSearch(array $group, mixed $search): bool
    {
        $query = static::normalizePermissionSearch((string) $search);

        if ($query === '') {
            return true;
        }

        return str_contains(static::permissionGroupSearchText($group), $query);
    }

    protected static function normalizePermissionSearch(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->squish()
            ->toString();
    }

    /**
     * @return array<string, array<int, array{key: string, label: string, description: string, permissions: array<string, array{name: string, label: string}>}>>
     */
    public static function permissionGroups(?string $type = null): array
    {
        static::$permissionGroupsCache ??= static::buildPermissionGroups();

        return $type === null
            ? static::$permissionGroupsCache
            : (static::$permissionGroupsCache[$type] ?? []);
    }

    /**
     * @return array<string, array<int, array{key: string, label: string, description: string, permissions: array<string, array{name: string, label: string}>}>>
     */
    protected static function buildPermissionGroups(): array
    {
        $groups = array_fill_keys(array_keys(static::PERMISSION_TYPES), []);
        $permissionModel = Utils::getPermissionModel();

        $permissionNames = $permissionModel::query()
            ->where('guard_name', static::guardName())
            ->orderBy('name')
            ->pluck('name');

        foreach ($permissionNames as $permissionName) {
            [$action, $subject] = static::permissionParts((string) $permissionName);

            if (static::shouldHidePermission((string) $permissionName, $subject)) {
                continue;
            }

            $type = static::permissionCategory((string) $permissionName, $action, $subject);
            $groupKey = static::permissionGroupKey($type, $subject);

            $groups[$type][$groupKey] ??= [
                'key' => $groupKey,
                'label' => static::subjectLabel($subject, $type),
                'description' => static::subjectDescription($subject, $type),
                'permissions' => [],
            ];

            $permissionKey = static::permissionStateKey((string) $permissionName);
            $groups[$type][$groupKey]['permissions'][$permissionKey] = [
                'name' => (string) $permissionName,
                'label' => static::permissionActionLabel($action, (string) $permissionName),
            ];
        }

        foreach ($groups as $type => $typeGroups) {
            uasort($typeGroups, fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));
            $groups[$type] = array_values($typeGroups);
        }

        return $groups;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function permissionParts(string $permission): array
    {
        $separator = (string) config('filament-shield.permissions.separator', ':');

        if ($separator !== '' && str_contains($permission, $separator)) {
            [$action, $subject] = explode($separator, $permission, 2);

            return [static::normalizePermissionAction($action), static::normalizePermissionSubject($subject)];
        }

        if (preg_match('/^([a-zA-Z]+)[._-](.+)$/', $permission, $matches)) {
            return [static::normalizePermissionAction($matches[1]), static::normalizePermissionSubject($matches[2])];
        }

        return ['Acesso', static::normalizePermissionSubject($permission)];
    }

    protected static function normalizePermissionAction(string $action): string
    {
        return Str::of($action)
            ->replace(['-', '_', '.'], ' ')
            ->studly()
            ->toString();
    }

    protected static function normalizePermissionSubject(string $subject): string
    {
        $subject = class_basename(str_replace('/', '\\', $subject));

        return Str::of($subject)
            ->replaceEnd('Resource', '')
            ->replaceEnd('Page', '')
            ->replaceEnd('Widget', '')
            ->studly()
            ->toString();
    }

    protected static function permissionCategory(string $permissionName, string $action, string $subject): string
    {
        $haystack = static::normalizePermissionSearch($permissionName . ' ' . $subject);

        if (static::isPanelAccessSubject($permissionName, $subject)) {
            return 'panels';
        }

        if (static::isReportSubject($subject, $haystack)) {
            return 'reports';
        }

        if (static::isDashboardSubject($subject, $haystack)) {
            return 'dashboard';
        }

        if (static::isPageSubject($subject, $haystack)) {
            return 'pages';
        }

        if (static::isResourceAction($action)) {
            return 'resources';
        }

        return 'legacy';
    }

    protected static function isPanelAccessSubject(string $permissionName, string $subject): bool
    {
        return str_starts_with($permissionName, 'AccessPanel:')
            || $subject === 'Admin'
            || $subject === 'Escola'
            || $subject === 'Professores';
    }

    protected static function isResourceAction(string $action): bool
    {
        return in_array($action, [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'Restore',
            'RestoreAny',
            'ForceDelete',
            'ForceDeleteAny',
            'Replicate',
            'Reorder',
        ], true);
    }

    protected static function isPageSubject(string $subject, string $haystack): bool
    {
        return in_array($subject, [
            'AttendanceManagement',
            'BackupSettings',
            'MailSettings',
            'TransferHistory',
        ], true) || str_contains($haystack, 'page');
    }

    protected static function isReportSubject(string $subject, string $haystack): bool
    {
        return $subject === 'Relatorios'
            || str_contains($haystack, 'relatorio')
            || str_contains($haystack, 'report');
    }

    protected static function isDashboardSubject(string $subject, string $haystack): bool
    {
        return in_array($subject, [
            'Dashboard',
            'StatsOverview',
            'CandidatesByProvinceChart',
            'CandidateStatusChart',
            'StudentStatusChart',
            'StudentManagement',
            'StudentsByCourseChart',
        ], true)
            || str_contains($haystack, 'dashboard')
            || str_contains($haystack, 'widget');
    }

    protected static function shouldHidePermission(string $permissionName, string $subject): bool
    {
        $haystack = static::normalizePermissionSearch($permissionName . ' ' . $subject);

        return $subject === 'CoursePlan'
            || str_contains($haystack, 'cadete')
            || str_contains($haystack, 'cadet')
            || str_contains($haystack, 'agent')
            || str_contains($haystack, 'activitylog')
            || str_contains($haystack, 'activity log')
            || str_contains($haystack, 'audit');
    }

    protected static function subjectLabel(string $subject, string $type): string
    {
        $labels = [
            'AcademicYear' => 'Anos Lectivos',
            'Admin' => 'Painel Admin',
            'AttendanceManagement' => 'Posto / Presenças',
            'BackupSettings' => 'Cópias de Segurança',
            'Candidate' => 'Alistados',
            'CandidateTransferHistory' => 'Histórico de Transferências',
            'Certificado' => 'Certificados',
            'Course' => 'Cursos',
            'CourseMap' => 'Mapas e Planos de Curso',
            'CoursePlan' => 'Planos de Curso (integrado)',
            'Dashboard' => 'Painel de Controlo',
            'Document' => 'Documentos',
            'EquipmentAssignment' => 'Atribuição de Meios',
            'Escola' => 'Painel da Escola',
            'Evaluation' => 'Avaliações',
            'Institution' => 'Instituições',
            'InstitutionType' => 'Tipos de Instituição',
            'MailSettings' => 'Configurações de E-mail',
            'Pauta' => 'Mini Pauta',
            'PautaGeral' => 'Pauta Geral',
            'Provenance' => 'Órgãos de Proveniência',
            'Professores' => 'Painel do Professor',
            'Rank' => 'Patentes',
            'RecruitmentType' => 'Tipos de Recrutamento',
            'Relatorios' => 'Relatórios',
            'Role' => 'Acessos',
            'StatsOverview' => 'Resumo do Painel',
            'CandidateStatusChart' => 'Gráfico de Estado dos Alistados',
            'CandidatesByProvinceChart' => 'Gráfico por Província',
            'StudentManagement' => 'Gestão de Formandos',
            'StudentStatusChart' => 'Gráfico de Estado dos Formandos',
            'StudentsByCourseChart' => 'Gráfico de Alunos por Curso',
            'Student' => 'Gestão de Formandos',
            'StudentClass' => 'Turmas',
            'StudentClassEnrollment' => 'Gestão de Formandos',
            'StudentLeave' => 'Dispensas e Faltas',
            'StudentTransferHistory' => 'Histórico de Transferências de Formandos',
            'StudentType' => 'Tipos de Formando',
            'Subject' => 'Disciplinas',
            'Trainer' => 'Formadores',
            'TrainerClassAssignment' => 'Atribuição de Formadores',
            'TransferHistory' => 'Histórico de Transferências',
            'User' => 'Utilizadores',
        ];

        return $labels[$subject] ?? Str::headline(Str::snake($subject, ' '));
    }

    protected static function subjectDescription(string $subject, string $type): string
    {
        return match ($type) {
            'panels' => 'Permite entrar neste painel após o login',
            'pages' => 'Página do painel administrativo',
            'reports' => 'Relatórios e mapas do sistema',
            'dashboard' => 'Cartões e gráficos do Painel de Controlo',
            'legacy' => 'Permissões gerais do sistema',
            default => 'Permissões do recurso ' . static::subjectLabel($subject, $type),
        };
    }

    protected static function permissionActionLabel(string $action, string $permissionName): string
    {
        $labels = [
            'ViewAny' => 'Ver Listagem',
            'View' => 'Visualizar',
            'Create' => 'Criar',
            'Update' => 'Editar',
            'Delete' => 'Eliminar',
            'DeleteAny' => 'Eliminar em Massa',
            'Restore' => 'Restaurar',
            'RestoreAny' => 'Restaurar Todos',
            'ForceDelete' => 'Eliminar Permanentemente',
            'ForceDeleteAny' => 'Eliminar Permanentemente Todos',
            'Replicate' => 'Duplicar',
            'Reorder' => 'Reordenar',
            'Export' => 'Exportar',
            'Import' => 'Importar',
            'AccessPanel' => 'Permitir acesso',
            'Acesso' => 'Acesso',
        ];

        return $labels[$action] ?? Str::headline($permissionName);
    }

    protected static function permissionGroupKey(string $type, string $subject): string
    {
        return $type . '-' . static::permissionStateKey($subject);
    }

    protected static function permissionStateKey(string $permission): string
    {
        return 'p' . substr(md5($permission), 0, 12);
    }

    protected static function emptyPermissionGroupLabel(string $type): string
    {
        return static::PERMISSION_TYPES[$type] ?? 'Permissões';
    }

    public static function setAllPermissionStates(callable $set, bool $state): null
    {
        foreach (static::permissionGroups() as $type => $groups) {
            foreach ($groups as $group) {
                $set("permission_groups.{$type}.{$group['key']}", $state ? array_keys($group['permissions']) : []);
            }
        }

        return null;
    }

    public static function formDataWithPermissionState(array $data, Role $role): array
    {
        $role->loadMissing('permissions');

        return array_merge(
            $data,
            static::permissionStateFromNames($role->permissions->pluck('name')->all()),
        );
    }

    /**
     * @param  iterable<int, string>  $permissions
     * @return array<string, mixed>
     */
    public static function permissionStateFromNames(iterable $permissions): array
    {
        $selected = [];

        foreach ($permissions as $permission) {
            $selected[(string) $permission] = true;
        }

        $permissionGroups = [];
        $totalPermissions = 0;
        $totalSelected = 0;

        foreach (static::permissionGroups() as $type => $groups) {
            foreach ($groups as $group) {
                $selectedPermissionKeys = [];

                foreach ($group['permissions'] as $permissionKey => $permission) {
                    $totalPermissions++;

                    if (isset($selected[$permission['name']])) {
                        $selectedPermissionKeys[] = $permissionKey;
                        $totalSelected++;
                    }
                }

                $permissionGroups[$type][$group['key']] = $selectedPermissionKeys;
            }
        }

        return [
            'permission_groups' => $permissionGroups,
            'select_all_permissions' => $totalPermissions > 0 && $totalSelected === $totalPermissions,
        ];
    }

    public static function stripPermissionFormData(array $data): array
    {
        unset(
            $data['permission_groups'],
            $data['permissions_search'],
            $data['select_all_permissions'],
        );

        $data['guard_name'] = static::guardName();

        return $data;
    }

    public static function syncPermissionsFromFormState(Role $role, array | Arrayable $state): void
    {
        $state = $state instanceof Arrayable ? $state->toArray() : $state;
        $permissions = [];

        foreach (static::permissionGroups() as $type => $groups) {
            foreach ($groups as $group) {
                $selectedPermissionKeys = data_get($state, "permission_groups.{$type}.{$group['key']}", []);

                if (! is_array($selectedPermissionKeys)) {
                    $selectedPermissionKeys = [];
                }

                foreach ($selectedPermissionKeys as $permissionKey) {
                    if (isset($group['permissions'][$permissionKey])) {
                        $permissions[] = $group['permissions'][$permissionKey]['name'];
                    }
                }
            }
        }

        $role->syncPermissions(array_values(array_unique($permissions)));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Perfil')
                    ->weight(FontWeight::Medium)
                    ->formatStateUsing(fn (?string $state): string => static::roleLabel((string) $state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Total de permissões')
                    ->counts('permissions')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                TableAction::make('create')
                    ->icon('heroicon-o-plus')
                    ->label('Criar Acesso')
                    ->color('primary')
                    ->url(fn (): string => static::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    TableAction::make('view')
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Role $record): string => static::getUrl('view', ['record' => $record])),
                    EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash')
                        ->hidden(fn (Role $record): bool => static::isProtectedRole($record)),
                ])
                    ->icon('heroicon-s-cog-6-tooth')
                    ->tooltip('Ações'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    protected static function guardName(): string
    {
        return Utils::getFilamentAuthGuard() ?: (string) config('auth.defaults.guard', 'web');
    }

    protected static function roleLabel(string $name): string
    {
        return match ($name) {
            'admin', 'administrador' => 'Administrador',
            'super_admin' => 'Super Administrador',
            'panel_user' => 'Utilizador do Painel',
            default => Str::headline(str_replace(['_', '-'], ' ', $name)),
        };
    }

    protected static function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, array_filter([
            config('filament-shield.super_admin.name'),
            config('filament-shield.panel_user.name'),
        ]), true);
    }
}
