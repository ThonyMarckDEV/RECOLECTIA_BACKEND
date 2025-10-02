<?php

namespace App\Http\Controllers\Recolectores;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Recolectores\utilities\validations\RecolectorValidations;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecolectorController extends Controller
{

    public function index(Request $request)
    {
        try {
            // Sin cambios en este método
            $recolectores = User::where('idRol', 3)
                ->with(['zona' => function ($query) {
                    $query->select('id', 'nombre', 'descripcion');
                }])
                ->select('idUsuario', 'username', 'name', 'estado', 'idZona')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Recolectores obtenidos exitosamente',
                'data' => $recolectores,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los recolectores: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // 1. Obtiene las reglas y mensajes de nuestra clase centralizada
            $validationData = RecolectorValidations::store();

            // 2. Valida la solicitud usando los datos obtenidos
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);
            
            // Si la validación falla, lanza una excepción
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // 3. Si la validación es exitosa, crea el usuario
            $user = User::create([
                'username' => $request->input('username'),
                'name' => $request->input('name'),
                'password' => bcrypt($request->input('password')),
                'perfil' => null,
                'idRol' => 3,
                'idZona' => $request->input('idZona'),
                'estado' => $request->input('estado'),
                'recolectPoints' => 0,
            ]);

            return response()->json(['message' => 'Recolector creado exitosamente', 'data' => $user], 201);

        } catch (ValidationException $e) {
            // Atrapa los errores de validación y los devuelve en un formato estándar 422
            return response()->json(['message' => 'Los datos proporcionados no son válidos.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el recolector: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $idUsuario)
    {
        try {
            // 1. Obtiene las reglas para la actualización
            $validationData = RecolectorValidations::update($idUsuario);
            
            // 2. Valida la solicitud
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            
            // 3. Busca el usuario y actualiza sus datos
            $user = User::where('idRol', 3)->findOrFail($idUsuario);

            $user->update([
                'username' => $request->input('username'),
                'name' => $request->input('name'),
                'password' => $request->input('password') ? bcrypt($request->input('password')) : $user->password,
                'idZona' => $request->input('idZona'),
                'estado' => $request->input('estado'),
            ]);

            return response()->json(['message' => 'Recolector actualizado exitosamente', 'data' => $user], 200);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Los datos proporcionados no son válidos.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el recolector: ' . $e->getMessage()], 500);
        }
    }
}