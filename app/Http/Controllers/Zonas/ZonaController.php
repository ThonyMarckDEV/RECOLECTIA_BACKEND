<?php
namespace App\Http\Controllers\Zonas;

use App\Models\Zona;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        try {
            $zona = Zona::create([
                'nombre' => $request->input('name'),
                'descripcion' => $request->input('description'),
            ]);
            return response()->json([
                'message' => 'Zona creada exitosamente',
                'data' => $zona,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la zona: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = Zona::select('id', 'nombre', 'descripcion');
            // Paginación
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
            return response()->json([
                'message' => 'Error al obtener las zonas: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        try {
            $zona = Zona::findOrFail($id);
            $zona->update([
                'nombre' => $request->input('name'),
                'descripcion' => $request->input('description'),
            ]);
            return response()->json([
                'message' => 'Zona actualizada exitosamente',
                'data' => $zona,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la zona: ' . $e->getMessage(),
            ], 500);
        }
    }
}