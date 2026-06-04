<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Institution;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
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
        return $authUser->can('ViewAny:Institution');
    }

    public function view(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('View:Institution');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Institution');
    }

    public function update(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('Update:Institution');
    }

    public function delete(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('Delete:Institution');
    }

    public function restore(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('Restore:Institution');
    }

    public function forceDelete(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('ForceDelete:Institution');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Institution');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Institution');
    }

    public function replicate(AuthUser $authUser, Institution $institution): bool
    {
        return $authUser->can('Replicate:Institution');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Institution');
    }

}