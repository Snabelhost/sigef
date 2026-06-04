<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentTypePolicy
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
        return $authUser->can('ViewAny:StudentType');
    }

    public function view(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('View:StudentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentType');
    }

    public function update(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Update:StudentType');
    }

    public function delete(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Delete:StudentType');
    }

    public function restore(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Restore:StudentType');
    }

    public function forceDelete(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('ForceDelete:StudentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentType');
    }

    public function replicate(AuthUser $authUser, StudentType $studentType): bool
    {
        return $authUser->can('Replicate:StudentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentType');
    }

}