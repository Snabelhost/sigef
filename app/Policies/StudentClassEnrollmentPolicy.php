<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso StudentClassEnrollment (Gestão de Formandos)
 */
class StudentClassEnrollmentPolicy
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
        return $authUser->can('ViewAny:StudentClassEnrollment');
    }

    public function view(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('View:StudentClassEnrollment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentClassEnrollment');
    }

    public function update(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Update:StudentClassEnrollment');
    }

    public function delete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Delete:StudentClassEnrollment');
    }

    public function restore(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Restore:StudentClassEnrollment');
    }

    public function forceDelete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('ForceDelete:StudentClassEnrollment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentClassEnrollment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentClassEnrollment');
    }

    public function replicate(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Replicate:StudentClassEnrollment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentClassEnrollment');
    }
}
