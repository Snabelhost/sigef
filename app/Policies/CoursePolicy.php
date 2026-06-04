<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Course;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
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
        return $authUser->can('ViewAny:Course');
    }

    public function view(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('View:Course');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Course');
    }

    public function update(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('Update:Course');
    }

    public function delete(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('Delete:Course');
    }

    public function restore(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('Restore:Course');
    }

    public function forceDelete(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('ForceDelete:Course');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Course');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Course');
    }

    public function replicate(AuthUser $authUser, Course $course): bool
    {
        return $authUser->can('Replicate:Course');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Course');
    }

}