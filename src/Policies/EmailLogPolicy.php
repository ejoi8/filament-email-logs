<?php

namespace Ejoi8\FilamentEmailLogs\Policies;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailLogPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }

    public function view(Authenticatable $user, EmailLog $emailLog): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }

    public function create(Authenticatable $user): bool
    {
        return false;
    }

    public function update(Authenticatable $user, EmailLog $emailLog): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }

    public function delete(Authenticatable $user, EmailLog $emailLog): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }

    public function restore(Authenticatable $user, EmailLog $emailLog): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }

    public function forceDelete(Authenticatable $user, EmailLog $emailLog): bool
    {
        return EmailLogAuthorization::canAccess($user);
    }
}
