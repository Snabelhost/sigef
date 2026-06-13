<?php

namespace App\Services;

use App\Models\Effective;
use App\Models\Trainer;
use App\Models\User;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffLoginAccountService
{
    private const TRAINER_ROLE = 'professores_user';

    private const EFFECTIVE_ROLE = 'escola_user';

    public function assignTrainerPassword(Trainer $trainer, string $email, string $password, bool $isActive = true): User
    {
        return $this->assignPassword(
            record: $trainer,
            email: $email,
            password: $password,
            isActive: $isActive,
            roleName: self::TRAINER_ROLE,
        );
    }

    public function assignEffectivePassword(Effective $effective, string $email, string $password, bool $isActive = true): User
    {
        return $this->assignPassword(
            record: $effective,
            email: $email,
            password: $password,
            isActive: $isActive,
            roleName: self::EFFECTIVE_ROLE,
        );
    }

    private function assignPassword(Trainer|Effective $record, string $email, string $password, bool $isActive, string $roleName): User
    {
        return DB::transaction(function () use ($record, $email, $password, $isActive, $roleName): User {
            $email = Str::lower(trim($email));

            if ($email === '') {
                throw ValidationException::withMessages([
                    'email' => 'Informe o e-mail de login.',
                ]);
            }

            $password = trim($password);

            if ($password === '') {
                throw ValidationException::withMessages([
                    'password' => 'Informe a senha de login.',
                ]);
            }

            $linkedUser = $record->user;
            $existingUser = User::query()
                ->where('email', $email)
                ->first();

            if ($linkedUser && $existingUser && (int) $linkedUser->getKey() !== (int) $existingUser->getKey()) {
                throw ValidationException::withMessages([
                    'email' => 'Este registo ja esta vinculado a outra conta. Remova ou altere o vinculo antes de usar este e-mail.',
                ]);
            }

            $user = $linkedUser ?: $existingUser ?: new User();

            $this->ensureUserCanBeLinked($user, $record);

            $user->fill([
                'name' => $this->recordName($record),
                'email' => $email,
                'institution_id' => $record->institution_id,
                'phone' => $record->phone,
                'is_active' => $isActive,
            ]);
            $user->password = Hash::make($password);
            $user->save();

            if ((int) $record->user_id !== (int) $user->getKey()) {
                $record->forceFill(['user_id' => $user->getKey()])->save();
            }

            $this->assignRole($user, $roleName);

            return $user->fresh(['roles']) ?? $user;
        });
    }

    private function ensureUserCanBeLinked(User $user, Trainer|Effective $record): void
    {
        if (! $user->exists) {
            return;
        }

        $query = $record instanceof Trainer
            ? Trainer::query()->where('user_id', $user->getKey())
            : Effective::query()->where('user_id', $user->getKey());

        if ($record->exists) {
            $query->whereKeyNot($record->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail ja esta vinculado a outro registo.',
            ]);
        }
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $profilePermissions = DefaultRolePermissions::profiles()[$roleName] ?? [];

        if ($profilePermissions !== []) {
            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $profilePermissions)
                ->get();

            if ($permissions->isNotEmpty()) {
                $role->givePermissionTo($permissions);
            }
        }

        if (! $user->hasRole($roleName)) {
            $user->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function recordName(Model $record): string
    {
        $name = trim((string) $record->getAttribute('full_name'));

        return $name !== '' ? $name : 'Utilizador SIGEF';
    }
}
