<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CourseMap;
use Illuminate\Auth\Access\HandlesAuthorization;

class CourseMapPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CourseMap');
    }

    public function view(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('View:CourseMap');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CourseMap');
    }

    public function update(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('Update:CourseMap');
    }

    public function delete(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('Delete:CourseMap');
    }

    public function restore(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('Restore:CourseMap');
    }

    public function forceDelete(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('ForceDelete:CourseMap');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CourseMap');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CourseMap');
    }

    public function replicate(AuthUser $authUser, CourseMap $courseMap): bool
    {
        return $authUser->can('Replicate:CourseMap');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CourseMap');
    }

}
