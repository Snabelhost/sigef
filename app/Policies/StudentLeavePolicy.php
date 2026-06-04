<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentLeave;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentLeavePolicy
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
        return $authUser->can('ViewAny:StudentLeave');
    }

    public function view(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('View:StudentLeave');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentLeave');
    }

    public function update(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('Update:StudentLeave');
    }

    public function delete(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('Delete:StudentLeave');
    }

    public function restore(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('Restore:StudentLeave');
    }

    public function forceDelete(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('ForceDelete:StudentLeave');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentLeave');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentLeave');
    }

    public function replicate(AuthUser $authUser, StudentLeave $studentLeave): bool
    {
        return $authUser->can('Replicate:StudentLeave');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentLeave');
    }

}