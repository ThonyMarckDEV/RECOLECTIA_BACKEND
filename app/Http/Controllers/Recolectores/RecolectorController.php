<?php

namespace App\Http\Controllers\Recolectores;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecolectorController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'username' => 'required|string|unique:usuarios,username|max:255',
                'name' => 'required|string|max:255',
                'estado' => 'required|in:0,1',
            ]);

            // Crear el usuario con idRol = 3 (recolector)
            $user = User::create([
                'username' => $request->input('username'),
                'name' => $request->input('name'),
                'perfil' => null,
                'idRol' => 3, // Fijo para recolectores
                'estado' => $request->input('estado'),
                'recolectPoints' => 0, // Por defecto
            ]);

            return response()->json([
                'message' => 'Recolector creado exitosamente',
                'data' => [
                    'idUsuario' => $user->idUsuario,
                    'username' => $user->username,
                    'name' => $user->name,
                    'perfil' => $user->perfil,
                    'estado' => $user->estado,
                    'recolectPoints' => $user->recolectPoints,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el recolector: ' . $e->getMessage(),
            ], 500);
        }
    }
}