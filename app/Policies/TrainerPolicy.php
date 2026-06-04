<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trainer;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainerPolicy
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
        return $authUser->can('ViewAny:Trainer');
    }

    public function view(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('View:Trainer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trainer');
    }

    public function update(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Update:Trainer');
    }

    public function delete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Delete:Trainer');
    }

    public function restore(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Restore:Trainer');
    }

    public function forceDelete(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('ForceDelete:Trainer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trainer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trainer');
    }

    public function replicate(AuthUser $authUser, Trainer $trainer): bool
    {
        return $authUser->can('Replicate:Trainer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trainer');
    }

}