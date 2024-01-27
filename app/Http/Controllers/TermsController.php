<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TermsController extends Controller
{
    public function acceptTerms(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->update(['accepted_terms' => true]);
            return response()->json(['ok' => true]); // Puedes enviar un nuevo token si es necesario.
        }

        return response()->json(['ok' => false]);
    }

}
