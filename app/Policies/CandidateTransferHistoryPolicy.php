<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CandidateTransferHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidateTransferHistoryPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CandidateTransferHistory');
    }

    public function view(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('View:CandidateTransferHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CandidateTransferHistory');
    }

    public function update(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('Update:CandidateTransferHistory');
    }

    public function delete(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('Delete:CandidateTransferHistory');
    }

    public function restore(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('Restore:CandidateTransferHistory');
    }

    public function forceDelete(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('ForceDelete:CandidateTransferHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CandidateTransferHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CandidateTransferHistory');
    }

    public function replicate(AuthUser $authUser, CandidateTransferHistory $candidateTransferHistory): bool
    {
        return $authUser->can('Replicate:CandidateTransferHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CandidateTransferHistory');
    }

}
