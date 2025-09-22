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
                    'idZona' => $user->idZona,
                    'zona' => $user->zona?->nombre,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el perfil: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateZona(Request $request)
    {
        try {
            $request->validate([
                'idZona' => 'required|integer|exists:zonas,id',
            ]);

            $user = Auth::user();
            $user->update([
                'idZona' => $request->input('idZona'),
            ]);

            return response()->json([
                'message' => 'Zona actualizada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la zona: ' . $e->getMessage(),
            ], 500);
        }
    }
}