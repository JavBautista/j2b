<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels J2B
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado por tienda - solo admins/superadmins de esa tienda
Broadcast::channel('shop.{shopId}', function ($user, $shopId) {
    // Verificar que el usuario pertenezca a la tienda Y sea admin/superadmin
    return (int) $user->shop_id === (int) $shopId && 
           $user->roles->whereIn('id', [1, 2])->isNotEmpty(); // roles 1,2 = admin/superadmin
});
