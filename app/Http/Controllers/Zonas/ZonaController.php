<?php

namespace App\Http\Controllers\Zonas;

use App\Http\Controllers\Zonas\utilities\validations\ZonaValidations;
use App\Models\Zona;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ZonaController extends Controller
{
    public function store(Request $request)
    {
        try {
            // 2. Usa la clase para validar
            $validationData = ZonaValidations::store();
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $zona = Zona::create([
                'nombre' => $request->input('name'),
                'descripcion' => $request->input('description'),
            ]);

            return response()->json(['message' => 'Zona creada exitosamente', 'data' => $zona], 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Los datos proporcionados no son válidos.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la zona: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        // El método index no necesita validación, se queda igual.
        try {
            $query = Zona::select('id', 'nombre', 'descripcion');
            $perPage = $request->input('per_page', 10);
            $zonas = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'message' => 'Zonas obtenidas exitosamente',
                'data' => $zonas->items(),
                'pagination' => [
                    'current_page' => $zonas->currentPage(),
                    'last_page' => $zonas->lastPage(),
                    'per_page' => $zonas->perPage(),
                    'total' => $zonas->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las zonas: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // 3. Usa la clase para validar la actualización
            $validationData = ZonaValidations::update($id);
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $zona = Zona::findOrFail($id);
            $zona->update([
                'nombre' => $request->input('name'),
                'descripcion' => $request->input('description'),
            ]);

            return response()->json(['message' => 'Zona actualizada exitosamente', 'data' => $zona], 200);
            
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Los datos proporcionados no son válidos.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la zona: ' . $e->getMessage()], 500);
        }
    }
}