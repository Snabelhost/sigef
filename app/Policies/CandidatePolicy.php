<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Candidate;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidatePolicy
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
        return $authUser->can('ViewAny:Candidate');
    }

    public function view(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('View:Candidate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Candidate');
    }

    public function update(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('Update:Candidate');
    }

    public function delete(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('Delete:Candidate');
    }

    public function restore(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('Restore:Candidate');
    }

    public function forceDelete(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('ForceDelete:Candidate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Candidate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Candidate');
    }

    public function replicate(AuthUser $authUser, Candidate $candidate): bool
    {
        return $authUser->can('Replicate:Candidate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Candidate');
    }

}