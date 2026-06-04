<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AcademicYear;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicYearPolicy
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
        return $authUser->can('ViewAny:AcademicYear');
    }

    public function view(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('View:AcademicYear');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AcademicYear');
    }

    public function update(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Update:AcademicYear');
    }

    public function delete(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Delete:AcademicYear');
    }

    public function restore(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Restore:AcademicYear');
    }

    public function forceDelete(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('ForceDelete:AcademicYear');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AcademicYear');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AcademicYear');
    }

    public function replicate(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Replicate:AcademicYear');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AcademicYear');
    }

}