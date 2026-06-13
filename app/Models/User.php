<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class User extends Authenticatable implements FilamentUser, HasTenants, Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPanelShield, AuditableTrait, HasApiTokens;
    use HasRoles {
        hasPermissionTo as traitHasPermissionTo;
        hasAnyPermission as traitHasAnyPermission;
        hasAllPermissions as traitHasAllPermissions;
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->prepareReferencesForDeletion();
        });
    }

    public const PANEL_ACCESS_PERMISSIONS = [
        'admin' => [
            'name' => 'Administração',
            'icon' => 'heroicon-o-cog-6-tooth',
            'url' => '/admin',
            'permission' => 'AccessPanel:Admin',
        ],
        'escola' => [
            'name' => 'Escola',
            'icon' => 'heroicon-o-academic-cap',
            'permission' => 'AccessPanel:Escola',
        ],
        'professores' => [
            'name' => 'Professores',
            'icon' => 'heroicon-o-user-group',
            'url' => '/professores',
            'permission' => 'AccessPanel:Professores',
        ],
    ];

    // ============================================================
    // SUPER ADMIN BYPASS - Solução definitiva para permissões
    // ============================================================
    // Override dos métodos de verificação de permissão do Spatie
    // para garantir que super_admin SEMPRE tenha acesso total.
    // ============================================================

    /**
     * Verifica se o usuário tem uma permissão específica.
     * Super admin sempre retorna true.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    /**
     * Verifica se o usuário tem qualquer das permissões fornecidas.
     * Super admin sempre retorna true.
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }
        return $this->traitHasAnyPermission(...$permissions);
    }

    /**
     * Verifica se o usuário tem todas as permissões fornecidas.
     * Super admin sempre retorna true.
     */
    public function hasAllPermissions(...$permissions): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }
        return $this->traitHasAllPermissions(...$permissions);
    }

    /**
     * Verifica se o usuário pode executar uma ação (can).
     * Super admin sempre retorna true.
     */
    public function can($abilities, $arguments = []): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }
        return parent::can($abilities, $arguments);
    }

    public function accessiblePanels(): array
    {
        if ($this->is_active === false) {
            return [];
        }

        $panels = [];

        if (
            $this->hasPanelAccessPermission('admin')
            || $this->hasRole('super_admin')
            || $this->hasRole('admin')
            || $this->hasRole('panel_user')
            || $this->hasRole('admin_admin')
        ) {
            $panels['admin'] = [
                ...self::PANEL_ACCESS_PERMISSIONS['admin'],
            ];
        }

        if (
            filled($this->institution_id)
            && $this->hasPanelAccessPermission('escola')
        ) {
            $panels['escola'] = [
                ...self::PANEL_ACCESS_PERMISSIONS['escola'],
                'url' => '/escola/' . $this->institution_id,
            ];
        }

        if (
            $this->hasPanelAccessPermission('professores')
        ) {
            $panels['professores'] = [
                ...self::PANEL_ACCESS_PERMISSIONS['professores'],
            ];
        }

        return $panels;
    }

    public function accessiblePanelIds(): array
    {
        return array_keys($this->accessiblePanels());
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return array_key_exists($panel->getId(), $this->accessiblePanels());
    }

    protected function hasPanelAccessPermission(string $panelId): bool
    {
        $permission = self::PANEL_ACCESS_PERMISSIONS[$panelId]['permission'] ?? null;

        if (! $permission) {
            return false;
        }

        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function getTenants(Panel $panel): Collection
    {
        return collect([$this->institution])->filter();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->institution_id === $tenant->id;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'institution_id',
        'phone',
        'is_active',
        'current_session_id',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function trainer(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    public function effective(): HasOne
    {
        return $this->hasOne(Effective::class);
    }

    private function prepareReferencesForDeletion(): void
    {
        $userId = (int) $this->getKey();

        foreach ([
            ['evaluations', 'evaluated_by'],
            ['student_leaves', 'approved_by'],
        ] as [$table, $column]) {
            if (! $this->hasUserReferenceColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, $userId)
                ->update([$column => null]);
        }

        $replacementId = null;

        foreach ([
            ['equipment_assignments', 'assigned_by'],
            ['trainer_subject_authorizations', 'authorized_by'],
        ] as [$table, $column]) {
            if (! $this->hasUserReferenceColumn($table, $column)) {
                continue;
            }

            $hasReferences = DB::table($table)
                ->where($column, $userId)
                ->exists();

            if (! $hasReferences) {
                continue;
            }

            $replacementId ??= $this->replacementUserIdForRequiredReferences();

            if (! $replacementId) {
                throw new \RuntimeException('Nao foi possivel eliminar este utilizador porque existem registos vinculados e nao ha outro utilizador para assumir essas referencias.');
            }

            DB::table($table)
                ->where($column, $userId)
                ->update([$column => $replacementId]);
        }

        $this->tokens()->delete();
        $this->roles()->detach();
        $this->permissions()->detach();
    }

    private function replacementUserIdForRequiredReferences(): ?int
    {
        $currentUserId = auth()->id();

        if ($currentUserId && (int) $currentUserId !== (int) $this->getKey()) {
            return (int) $currentUserId;
        }

        return static::query()
            ->where('id', '!=', $this->getKey())
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'admin', 'admin_admin']))
            ->value('id')
            ?? static::query()
                ->where('id', '!=', $this->getKey())
                ->value('id');
    }

    private function hasUserReferenceColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }
}
