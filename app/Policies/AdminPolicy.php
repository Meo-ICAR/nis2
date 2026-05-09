<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AdminPolicy
{
    /**
     * Determine whether the user can access admin features.
     */
    public function accessAdminPanel(User $user): Response
    {
        return $user->is_admin && $user->is_active
            ? Response::allow()
            : Response::deny('Solo gli amministratori attivi possono accedere al pannello di amministrazione.');
    }

    /**
     * Determine whether the user can manage users.
     */
    public function manageUsers(User $user): Response
    {
        return $user->is_admin && $user->is_active
            ? Response::allow()
            : Response::deny('Solo gli amministratori attivi possono gestire gli utenti.');
    }

    /**
     * Determine whether the user can sync WSO2 users.
     */
    public function syncWSO2Users(User $user): Response
    {
        return $user->is_admin && $user->is_active
            ? Response::allow()
            : Response::deny('Solo gli amministratori attivi possono sincronizzare gli utenti WSO2.');
    }

    /**
     * Determine whether the user can sync WSO2 applications.
     */
    public function syncWSO2Applications(User $user): Response
    {
        return $user->is_admin && $user->is_active
            ? Response::allow()
            : Response::deny('Solo gli amministratori attivi possono sincronizzare le applicazioni WSO2.');
    }

    /**
     * Determine whether the user can view admin statistics.
     */
    public function viewAdminStats(User $user): Response
    {
        return $user->is_admin && $user->is_active
            ? Response::allow()
            : Response::deny('Solo gli amministratori attivi possono visualizzare le statistiche.');
    }
}
