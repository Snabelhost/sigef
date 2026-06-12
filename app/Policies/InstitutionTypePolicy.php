<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InstitutionType;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionTypePolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InstitutionType');
    }

    public function view(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('View:InstitutionType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InstitutionType');
    }

    public function update(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('Update:InstitutionType');
    }

    public function delete(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('Delete:InstitutionType');
    }

    public function restore(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('Restore:InstitutionType');
    }

    public function forceDelete(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('ForceDelete:InstitutionType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InstitutionType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InstitutionType');
    }

    public function replicate(AuthUser $authUser, InstitutionType $institutionType): bool
    {
        return $authUser->can('Replicate:InstitutionType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InstitutionType');
    }

}
