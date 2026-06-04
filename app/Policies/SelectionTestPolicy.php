<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SelectionTest;
use Illuminate\Auth\Access\HandlesAuthorization;

class SelectionTestPolicy
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
        return $authUser->can('ViewAny:SelectionTest');
    }

    public function view(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('View:SelectionTest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SelectionTest');
    }

    public function update(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('Update:SelectionTest');
    }

    public function delete(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('Delete:SelectionTest');
    }

    public function restore(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('Restore:SelectionTest');
    }

    public function forceDelete(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('ForceDelete:SelectionTest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SelectionTest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SelectionTest');
    }

    public function replicate(AuthUser $authUser, SelectionTest $selectionTest): bool
    {
        return $authUser->can('Replicate:SelectionTest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SelectionTest');
    }

}