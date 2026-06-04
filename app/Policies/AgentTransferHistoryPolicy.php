<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AgentTransferHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgentTransferHistoryPolicy
{
    use HandlesAuthorization;
    /**
     * Grant all permissions when in the Escola panel.
     */
    public function before(\Illuminate\Foundation\Auth\User $user, string $ability): ?bool
    {
        // Check direct URL
        if (request()?->is('escola/*') || request()?->is('*/escola/*')) {
            return true;
        }

        // Check HTTP referer for Livewire requests
        $referer = request()?->headers?->get('referer', '');
        if (str_contains($referer, '/escola/')) {
            return true;
        }

        // Check Filament panel context
        try {
            if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'escola') {
                return true;
            }
        } catch (\Throwable $e) {}

        return null;
    }
    
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