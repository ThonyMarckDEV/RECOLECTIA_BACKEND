<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Reports\utilities\validations\ReportValidations;
use App\Models\Report;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = $user->idUsuario;
            $rolId = $user->idRol;

            // Validate request parameters
            $request->validate([
                'status' => 'nullable|in:0,1,2,3', // Allow empty string or valid status values
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Initialize the query for reports with user relationship
            $query = Report::query()
                ->with('user:idUsuario,name')
                ->select('id', 'idUsuario', 'description', 'image_url', 'latitude', 'longitude', 'status', 'fecha', 'hora', 'created_at', 'updated_at');

            // If the user is not an admin (idRol != 1), filter by user ID
            if ($rolId != 1) {
                $query->where('idUsuario', $userId);
            }

            // Apply filters if provided
            if ($request->filled('status')) { // Use filled() to check for non-empty values
                Log::info('Applying status filter', ['status' => $request->status]);
                $query->where('status', $request->status);
            } else {
                Log::info('No status filter applied (Todos selected)');
            }

            if ($request->filled('fecha_inicio')) {
                Log::info('Applying fecha_inicio filter', ['fecha_inicio' => $request->fecha_inicio]);
                $query->whereDate('fecha', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                Log::info('Applying fecha_fin filter', ['fecha_fin' => $request->fecha_fin]);
                $query->whereDate('fecha', '<=', $request->fecha_fin);
            }

            // Apply pagination
            $perPage = $request->input('per_page', 10);
            $reportes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Log the query for debugging
            Log::info('Executed query', ['sql' => $query->toSql(), 'bindings' => $query->getBindings(), 'results_count' => $reportes->total()]);

            return response()->json([
                'message' => 'Reportes obtenidos exitosamente',
                'data' => $reportes->items(),
                'pagination' => [
                    'current_page' => $reportes->currentPage(),
                    'last_page' => $reportes->lastPage(),
                    'per_page' => $reportes->perPage(),
                    'total' => $reportes->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in ReportController::index', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error al obtener los reportes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // 1. Validar todo de una sola vez
            $validationData = ReportValidations::store();
            $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);
            
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            $userId = $validatedData['idUsuario'];

            // Procesar la imagen
            $photoData = $validatedData['photo'];
            $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
            $photoData = str_replace(' ', '+', $photoData);
            $image = base64_decode($photoData);

            if ($image === false) {
                return response()->json(['message' => 'Error al decodificar la imagen'], 400);
            }

            // Nombre y ruta
            $imageName = 'report_' . Str::random(10) . '.jpg';
            $imagePath = "usuarios/{$userId}/reportes/{$imageName}";

            // Guardar en storage/app/public/usuarios/...
            Storage::disk('public')->put($imagePath, $image);

            // Generar URL pública (/storage/usuarios/...)
            $imageUrl = Storage::url($imagePath);

            // Crear el reporte
            $report = Report::create([
                'idUsuario'   => $userId,
                'description' => $validatedData['description'],
                'image_url'   => $imageUrl,
                'latitude'    => $validatedData['latitude'],
                'longitude'   => $validatedData['longitude'],
                'status'      => 0,
                'fecha'       => Carbon::now()->toDateString(),
                'hora'        => Carbon::now()->toTimeString(),
            ]);

            return response()->json([
                'message' => 'Reporte creado exitosamente',
                'data'    => $report,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.', 
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in ReportController::store', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al crear el reporte.'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:1,2,3', // Aceptado (1), Resuelto (2), Rechazado (3)
            ]);

            $report = Report::findOrFail($id);
            $newStatus = $request->input('status');
            $oldStatus = $report->status;

            $report->update([
                'status' => $newStatus,
            ]);

            // Update user's recolectPoints based on status change
            if ($newStatus == 1 && $oldStatus != 1) {
                $user = User::find($report->idUsuario);
                if ($user) {
                    $user->recolectPoints += 100;
                    $user->save();
                }
            } elseif ($newStatus == 2 && $oldStatus != 2) {
                $user = User::find($report->idUsuario);
                if ($user) {
                    $user->recolectPoints += 200;
                    $user->save();
                }
            }

            return response()->json([
                'message' => 'Estado del reporte actualizado exitosamente',
                'report' => $report,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estado del reporte: ' . $e->getMessage(),
            ], 500);
        }
    }
}
