<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
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
        return $authUser->can('ViewAny:Student');
    }

    public function view(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('View:Student');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Student');
    }

    public function update(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Update:Student');
    }

    public function delete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Delete:Student');
    }

    public function restore(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Restore:Student');
    }

    public function forceDelete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('ForceDelete:Student');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Student');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Student');
    }

    public function replicate(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Replicate:Student');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Student');
    }

}