<?php

namespace App\Http\Controllers\PerCapita;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PerCapita\utilities\validations\PerCapitaValidations;
use App\Models\PerCapitaRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PerCapitaController extends Controller
{
    /**
     * Verifica si el usuario autenticado ya registró su basura hoy.
     */
    public function checkToday()
    {
        $userId = Auth::id();
        $alreadyExists = PerCapitaRecord::where('idUsuario', $userId)
            ->whereDate('record_date', Carbon::today())
            ->exists();

        return response()->json(['can_submit' => !$alreadyExists]);
    }

    /**
     * Guarda un nuevo registro de basura.
     */
    public function store(Request $request)
    {
        // Añadimos el id del usuario autenticado al request para validarlo todo junto
        $request->merge(['idUsuario' => Auth::id()]);

        try {
            $validationData = PerCapitaValidations::store();
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $record = PerCapitaRecord::create([
                'idUsuario'   => $request->idUsuario,
                'weight_kg'   => $request->weight_kg,
                'record_date' => Carbon::today(),
            ]);

            return response()->json([
                'message' => '¡Registro guardado con éxito! Tu GPC de hoy es ' . $request->weight_kg . ' kg.',
                'data' => $record
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar el registro.'], 500);
        }
    }

   /**
     * Devuelve los registros paginados del usuario autenticado y un resumen total.
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();
            $perPage = 2; // Registros por página, puedes ajustarlo

            // 1. Creamos la consulta base sin ejecutarla
            $baseQuery = PerCapitaRecord::where('idUsuario', $userId);

            // 2. Calculamos los totales usando la consulta base (sobre todos los registros)
            $total_days = $baseQuery->count();
            $total_weight_kg = $baseQuery->sum('weight_kg');

            // 3. Ahora sí, aplicamos orden y paginación a la consulta
            $recordsPaginados = $baseQuery->orderBy('record_date', 'desc')
                                        ->select('record_date', 'weight_kg')
                                        ->paginate($perPage);

            // 4. Devolvemos una respuesta estructurada con los datos y la paginación
            return response()->json([
                'message' => 'Registros obtenidos exitosamente',
                'data' => $recordsPaginados->items(), // Los registros de la página actual
                'summary' => [
                    'total_days' => $total_days,
                    'total_weight_kg' => (float) $total_weight_kg, 
                ],
                'pagination' => [
                    'current_page' => $recordsPaginados->currentPage(),
                    'last_page' => $recordsPaginados->lastPage(),
                    'per_page' => $recordsPaginados->perPage(),
                    'total' => $recordsPaginados->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener tus registros.'], 500);
        }
    }
}