<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para o recurso Certificado
 */
class CertificadoPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Certificado');
    }

    public function view(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('View:Certificado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Certificado');
    }

    public function update(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Update:Certificado');
    }

    public function delete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Delete:Certificado');
    }

    public function restore(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Restore:Certificado');
    }

    public function forceDelete(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('ForceDelete:Certificado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Certificado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Certificado');
    }

    public function replicate(AuthUser $authUser, Student $student): bool
    {
        return $authUser->can('Replicate:Certificado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Certificado');
    }
}
