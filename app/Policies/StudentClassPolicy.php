<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentClass;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentClassPolicy
{
    use HandlesAuthorization;

    /**
     * Grant all permissions when in the Escola panel.
     * The Escola panel handles its own access control.
     */
    public function before(AuthUser $user, string $ability): ?bool
    {
        // Check direct URL
        if (request()?->is('escola/*') || request()?->is('*/escola/*')) {
            return true;
        }

        // Check HTTP referer for Livewire requests (/livewire/update)
        $referer = request()?->headers?->get('referer', '');
        if (str_contains($referer, '/escola/')) {
            return true;
        }

        // Check Filament panel context
        try {
            if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'escola') {
                return true;
            }
        } catch (\Throwable $e) {
            // Ignore if panel not resolved yet
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentClass');
    }

    public function view(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('View:StudentClass');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentClass');
    }

    public function update(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('Update:StudentClass');
    }

    public function delete(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('Delete:StudentClass');
    }

    public function restore(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('Restore:StudentClass');
    }

    public function forceDelete(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('ForceDelete:StudentClass');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentClass');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentClass');
    }

    public function replicate(AuthUser $authUser, StudentClass $studentClass): bool
    {
        return $authUser->can('Replicate:StudentClass');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentClass');
    }
}
