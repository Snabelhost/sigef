<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso Agent (que usa o modelo Student)
 */
class AgentPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Agent');
    }

    public function view(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('View:Agent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Agent');
    }

    public function update(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Update:Agent');
    }

    public function delete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Delete:Agent');
    }

    public function restore(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Restore:Agent');
    }

    public function forceDelete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('ForceDelete:Agent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Agent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Agent');
    }

    public function replicate(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Replicate:Agent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Agent');
    }
}
