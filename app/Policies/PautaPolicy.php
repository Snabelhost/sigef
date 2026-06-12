<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Evaluation;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso Pauta
 */
class PautaPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pauta');
    }

    public function view(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('View:Pauta');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pauta');
    }

    public function update(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Update:Pauta');
    }

    public function delete(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Delete:Pauta');
    }

    public function restore(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Restore:Pauta');
    }

    public function forceDelete(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('ForceDelete:Pauta');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pauta');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pauta');
    }

    public function replicate(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Replicate:Pauta');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pauta');
    }
}
