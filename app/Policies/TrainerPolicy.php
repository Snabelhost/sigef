<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trainer;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainerPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Trainer');
    }

    public function view(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('View:Trainer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trainer');
    }

    public function update(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Update:Trainer');
    }

    public function delete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Delete:Trainer');
    }

    public function restore(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Restore:Trainer');
    }

    public function forceDelete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('ForceDelete:Trainer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trainer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trainer');
    }

    public function replicate(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Replicate:Trainer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trainer');
    }

}
