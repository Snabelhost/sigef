<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecruitmentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentTypePolicy
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
        return $authUser->can('ViewAny:RecruitmentType');
    }

    public function view(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('View:RecruitmentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentType');
    }

    public function update(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('Update:RecruitmentType');
    }

    public function delete(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('Delete:RecruitmentType');
    }

    public function restore(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('Restore:RecruitmentType');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('ForceDelete:RecruitmentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentType');
    }

    public function replicate(AuthUser $authUser, RecruitmentType $recruitmentType): bool
    {
        return $authUser->can('Replicate:RecruitmentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentType');
    }

}