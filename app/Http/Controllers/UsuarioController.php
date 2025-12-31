<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function resetPassword(Request $request){
        // Validar los campos del formulario
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Obtener el usuario autenticado
        $user = $request->user();
        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['ok'=>false,'message' => 'La contraseña actual es incorrecta'], 422);
        }

        // Actualizar la contraseña del usuario
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['ok'=>true,'message' => 'Contraseña actualizada correctamente'], 200);
    }//resetPassword()

}
