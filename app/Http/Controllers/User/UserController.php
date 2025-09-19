<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'message' => 'Perfil obtenido exitosamente',
                'data' => [
                    'idUsuario' => $user->idUsuario,
                    'name' => $user->name,
                    'perfil' => $user->perfil,
                    'recolectPoints' => $user->recolectPoints,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el perfil: ' . $e->getMessage(),
            ], 500);
        }
    }
}