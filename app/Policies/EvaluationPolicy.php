<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Evaluation;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationPolicy
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
        return $authUser->can('ViewAny:Evaluation');
    }

    public function view(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('View:Evaluation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Evaluation');
    }

    public function update(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Update:Evaluation');
    }

    public function delete(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Delete:Evaluation');
    }

    public function restore(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Restore:Evaluation');
    }

    public function forceDelete(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('ForceDelete:Evaluation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Evaluation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Evaluation');
    }

    public function replicate(AuthUser $authUser, Evaluation $evaluation): bool
    {
        return $authUser->can('Replicate:Evaluation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Evaluation');
    }

}