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

class User extends Authenticatable implements FilamentUser, HasTenants, Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPanelShield, AuditableTrait, HasApiTokens;
    use HasRoles {
        hasPermissionTo as traitHasPermissionTo;
        hasAnyPermission as traitHasAnyPermission;
        hasAllPermissions as traitHasAllPermissions;
    }

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

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();
        
        // Super admin pode aceder a qualquer painel
        if ($this->hasRole('super_admin')) {
            return true;
        }
        
        // Acesso baseado em roles específicos do painel
        if ($this->hasRole($panelId . '_admin') || $this->hasRole($panelId . '_user')) {
            return true;
        }

        // Painel Admin - acesso para admin e panel_user
        if ($panelId === 'admin' && ($this->hasRole('admin') || $this->hasRole('panel_user'))) {
            return true;
        }

        // Painel Escola - precisa de institution_id e role adequado
        if ($panelId === 'escola') {
            if ($this->hasRole('escola_admin') || $this->hasRole('panel_user')) {
                return $this->institution_id !== null;
            }
        }

        // Painel DPQ
        if ($panelId === 'dpq' && $this->hasRole('dpq_admin')) {
            return $this->institution_id !== null;
        }

        // Painel Comando
        if ($panelId === 'comando' && $this->hasRole('comando_admin')) {
            return $this->institution_id !== null;
        }
        
        return false;
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
