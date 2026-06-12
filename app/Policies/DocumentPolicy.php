<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Document;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

public function viewAny(AuthUser $authUser): bool
    {
        // Allow if user has permission OR has received documents
        if ($authUser->can('ViewAny:Document')) {
            return true;
        }
        
        // Allow if user has sent any documents
        if (\App\Models\Document::where('sender_user_id', $authUser->id)->exists()) {
            return true;
        }
        
        // Allow if user is a recipient of any document
        return \App\Models\DocumentRecipient::where('user_id', $authUser->id)->exists();
    }

    public function view(AuthUser $authUser, Document $document): bool
    {
        // Allow if user has general permission
        if ($authUser->can('View:Document')) {
            return true;
        }
        
        // Allow if user is the sender
        if ($document->sender_user_id === $authUser->id) {
            return true;
        }
        
        // Allow if user is a recipient
        return $document->recipients()->where('user_id', $authUser->id)->exists();
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Document');
    }

    public function update(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Update:Document');
    }

    public function delete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Delete:Document');
    }

    public function restore(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Restore:Document');
    }

    public function forceDelete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('ForceDelete:Document');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Document');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Document');
    }

    public function replicate(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Replicate:Document');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Document');
    }

}
