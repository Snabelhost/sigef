<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CardTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CardTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CardTemplate');
    }

    public function view(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('View:CardTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CardTemplate');
    }

    public function update(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('Update:CardTemplate');
    }

    public function delete(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('Delete:CardTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CardTemplate');
    }

    public function restore(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('Restore:CardTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CardTemplate');
    }

    public function forceDelete(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('ForceDelete:CardTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CardTemplate');
    }

    public function replicate(AuthUser $authUser, CardTemplate $cardTemplate): bool
    {
        return $authUser->can('Replicate:CardTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CardTemplate');
    }
}
