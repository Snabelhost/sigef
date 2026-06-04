<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TrainerSubjectAuthorization;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainerSubjectAuthorizationPolicy
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
        return $authUser->can('ViewAny:TrainerSubjectAuthorization');
    }

    public function view(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('View:TrainerSubjectAuthorization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TrainerSubjectAuthorization');
    }

    public function update(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('Update:TrainerSubjectAuthorization');
    }

    public function delete(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('Delete:TrainerSubjectAuthorization');
    }

    public function restore(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('Restore:TrainerSubjectAuthorization');
    }

    public function forceDelete(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('ForceDelete:TrainerSubjectAuthorization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TrainerSubjectAuthorization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TrainerSubjectAuthorization');
    }

    public function replicate(AuthUser $authUser, TrainerSubjectAuthorization $trainerSubjectAuthorization): bool
    {
        return $authUser->can('Replicate:TrainerSubjectAuthorization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TrainerSubjectAuthorization');
    }

}