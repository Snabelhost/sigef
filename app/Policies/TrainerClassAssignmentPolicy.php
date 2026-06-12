<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trainer;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso TrainerClassAssignment (Atribuição de Turmas)
 */
class TrainerClassAssignmentPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TrainerClassAssignment');
    }

    public function view(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('View:TrainerClassAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TrainerClassAssignment');
    }

    public function update(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Update:TrainerClassAssignment');
    }

    public function delete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Delete:TrainerClassAssignment');
    }

    public function restore(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Restore:TrainerClassAssignment');
    }

    public function forceDelete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('ForceDelete:TrainerClassAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TrainerClassAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TrainerClassAssignment');
    }

    public function replicate(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Replicate:TrainerClassAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TrainerClassAssignment');
    }
}
