<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EquipmentAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentAssignmentPolicy
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
        return $authUser->can('ViewAny:EquipmentAssignment');
    }

    public function view(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('View:EquipmentAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipmentAssignment');
    }

    public function update(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('Update:EquipmentAssignment');
    }

    public function delete(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('Delete:EquipmentAssignment');
    }

    public function restore(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('Restore:EquipmentAssignment');
    }

    public function forceDelete(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('ForceDelete:EquipmentAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipmentAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipmentAssignment');
    }

    public function replicate(AuthUser $authUser, EquipmentAssignment $equipmentAssignment): bool
    {
        return $authUser->can('Replicate:EquipmentAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipmentAssignment');
    }

}