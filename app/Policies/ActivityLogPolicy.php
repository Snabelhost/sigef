<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
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
        return $authUser->can('ViewAny:ActivityLog');
    }

    public function view(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('View:ActivityLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ActivityLog');
    }

    public function update(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('Update:ActivityLog');
    }

    public function delete(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('Delete:ActivityLog');
    }

    public function restore(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('Restore:ActivityLog');
    }

    public function forceDelete(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('ForceDelete:ActivityLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ActivityLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ActivityLog');
    }

    public function replicate(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('Replicate:ActivityLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ActivityLog');
    }

}