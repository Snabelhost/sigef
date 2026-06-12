<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AgentTransferHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgentTransferHistoryPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AgentTransferHistory');
    }

    public function view(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('View:AgentTransferHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AgentTransferHistory');
    }

    public function update(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('Update:AgentTransferHistory');
    }

    public function delete(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('Delete:AgentTransferHistory');
    }

    public function restore(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('Restore:AgentTransferHistory');
    }

    public function forceDelete(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('ForceDelete:AgentTransferHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AgentTransferHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AgentTransferHistory');
    }

    public function replicate(AuthUser $authUser, AgentTransferHistory $agentTransferHistory): bool
    {
        return $authUser->can('Replicate:AgentTransferHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AgentTransferHistory');
    }

}
