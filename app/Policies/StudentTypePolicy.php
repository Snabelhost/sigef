<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentTypePolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentType');
    }

    public function view(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('View:StudentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentType');
    }

    public function update(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Update:StudentType');
    }

    public function delete(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Delete:StudentType');
    }

    public function restore(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Restore:StudentType');
    }

    public function forceDelete(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('ForceDelete:StudentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentType');
    }

    public function replicate(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Replicate:StudentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentType');
    }

}
