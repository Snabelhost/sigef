<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CoursePlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePlanPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CoursePlan');
    }

    public function view(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('View:CoursePlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CoursePlan');
    }

    public function update(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('Update:CoursePlan');
    }

    public function delete(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('Delete:CoursePlan');
    }

    public function restore(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('Restore:CoursePlan');
    }

    public function forceDelete(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('ForceDelete:CoursePlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CoursePlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CoursePlan');
    }

    public function replicate(AuthUser $authUser, CoursePlan $coursePlan): bool
    {
        return $authUser->can('Replicate:CoursePlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CoursePlan');
    }

}
