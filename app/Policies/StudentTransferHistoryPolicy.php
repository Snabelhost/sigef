<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentTransferHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentTransferHistoryPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentTransferHistory');
    }

    public function view(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('View:StudentTransferHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentTransferHistory');
    }

    public function update(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('Update:StudentTransferHistory');
    }

    public function delete(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('Delete:StudentTransferHistory');
    }

    public function restore(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('Restore:StudentTransferHistory');
    }

    public function forceDelete(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('ForceDelete:StudentTransferHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentTransferHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentTransferHistory');
    }

    public function replicate(AuthUser $authUser, StudentTransferHistory $studentTransferHistory): bool
    {
        return $authUser->can('Replicate:StudentTransferHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentTransferHistory');
    }

}
