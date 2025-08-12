<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin puede ver todas las plantillas
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede ver plantillas de su tienda
        return $user->hasRole('admin') && $user->shop !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ContractTemplate $contractTemplate): bool
    {
        // Solo admin puede ver plantillas
        return $user->hasRole('admin') && $user->shop !== null && $user->shop->id === $contractTemplate->shop_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Superadmin puede crear plantillas
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin puede crear plantillas para su tienda
        return $user->hasRole('admin') && $user->shop !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ContractTemplate $contractTemplate): bool
    {
        // Superadmin puede editar cualquier plantilla
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede editar plantillas de su tienda
        return $user->hasRole('admin') && $user->shop && $user->shop->id === $contractTemplate->shop_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContractTemplate $contractTemplate): bool
    {
        // Superadmin puede eliminar cualquier plantilla
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede eliminar plantillas de su tienda
        return $user->hasRole('admin') && $user->shop && $user->shop->id === $contractTemplate->shop_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContractTemplate $contractTemplate): bool
    {
        // Superadmin puede restaurar cualquier plantilla
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede restaurar plantillas de su tienda
        return $user->hasRole('admin') && $user->shop && $user->shop->id === $contractTemplate->shop_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContractTemplate $contractTemplate): bool
    {
        // Superadmin puede eliminar permanentemente cualquier plantilla
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede eliminar permanentemente plantillas de su tienda
        return $user->hasRole('admin') && $user->shop && $user->shop->id === $contractTemplate->shop_id;
    }
}
