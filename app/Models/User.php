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
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
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
            && (
                $this->hasPanelAccessPermission('escola')
                || $this->hasRole('escola_admin')
                || $this->hasRole('escola_user')
            )
        ) {
            $panels['escola'] = [
                ...self::PANEL_ACCESS_PERMISSIONS['escola'],
                'url' => '/escola/' . $this->institution_id,
            ];
        }

        if (
            $this->hasPanelAccessPermission('professores')
            || $this->hasRole('professores_admin')
            || $this->hasRole('professores_user')
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
}
