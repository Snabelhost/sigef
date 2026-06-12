<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Provenance;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProvenancePolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Provenance');
    }

    public function view(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('View:Provenance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Provenance');
    }

    public function update(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('Update:Provenance');
    }

    public function delete(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('Delete:Provenance');
    }

    public function restore(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('Restore:Provenance');
    }

    public function forceDelete(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('ForceDelete:Provenance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Provenance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Provenance');
    }

    public function replicate(AuthUser $authUser, Provenance $provenance): bool
    {
        return $authUser->can('Replicate:Provenance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Provenance');
    }

}
