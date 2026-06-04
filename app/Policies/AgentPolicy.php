<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso Agent (que usa o modelo Student)
 */
class AgentPolicy
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
        return $authUser->can('ViewAny:Agent');
    }

    public function view(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('View:Agent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Agent');
    }

    public function update(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Update:Agent');
    }

    public function delete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Delete:Agent');
    }

    public function restore(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Restore:Agent');
    }

    public function forceDelete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('ForceDelete:Agent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Agent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Agent');
    }

    public function replicate(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Replicate:Agent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Agent');
    }
}
