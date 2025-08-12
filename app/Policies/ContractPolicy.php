<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin puede ver todos los contratos
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede ver contratos de su tienda
        return $user->hasRole('admin') && $user->shop !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contract $contract): bool
    {
        // Superadmin puede ver cualquier contrato
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin puede ver contratos de clientes de su tienda
        if ($user->hasRole('admin')) {
            return $user->shop && $contract->client && 
                   $user->shop->id === $contract->client->shop_id;
        }
        
        // Cliente puede ver SOLO sus propios contratos
        if ($user->hasRole('client')) {
            return $user->client && $contract->client_id === $user->client->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Superadmin puede crear contratos
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin puede crear contratos para su tienda
        return $user->hasRole('admin') && $user->shop !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contract $contract): bool
    {
        // Superadmin puede editar cualquier contrato
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin puede editar contratos de clientes de su tienda
        if ($user->hasRole('admin')) {
            return $user->shop && $contract->client && 
                   $user->shop->id === $contract->client->shop_id;
        }
        
        // Cliente puede firmar (actualizar signature_path) SOLO sus propios contratos
        if ($user->hasRole('client')) {
            return $user->client && $contract->client_id === $user->client->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contract $contract): bool
    {
        // Superadmin puede eliminar cualquier contrato
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Admin solo puede eliminar contratos de su tienda
        return $user->hasRole('admin') && $user->shop && $user->shop->id === $contract->client->shop_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contract $contract): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contract $contract): bool
    {
        return false;
    }
}
