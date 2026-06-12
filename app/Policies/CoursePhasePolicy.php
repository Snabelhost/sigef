<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CoursePhase;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePhasePolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CoursePhase');
    }

    public function view(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('View:CoursePhase');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CoursePhase');
    }

    public function update(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('Update:CoursePhase');
    }

    public function delete(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('Delete:CoursePhase');
    }

    public function restore(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('Restore:CoursePhase');
    }

    public function forceDelete(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('ForceDelete:CoursePhase');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CoursePhase');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CoursePhase');
    }

    public function replicate(AuthUser $authUser, CoursePhase $coursePhase): bool
    {
        return $authUser->can('Replicate:CoursePhase');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CoursePhase');
    }

}
