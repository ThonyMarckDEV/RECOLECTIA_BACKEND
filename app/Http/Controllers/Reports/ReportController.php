<?php

namespace App\Http\Controllers\Reports;

use App\Models\Report;
use App\Http\Controllers\Reports\utilities\ReportValidations;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
   public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Report::with('user:idUsuario,name')
                ->select('id', 'idUsuario', 'description', 'image_url', 'latitude', 'longitude', 'status', 'fecha', 'hora', 'created_at', 'updated_at');

            // Filtrar por idUsuario si el rol es cliente (idRol = 2)
            if ($user->idRol === 2) {
                $query->where('idUsuario', $user->idUsuario);
            }

            // Filtros
            if ($request->has('status')) {
                $status = $request->input('status');
                if (in_array($status, [0, 1, 2, 3])) {
                    $query->where('status', $status);
                }
            }

            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $fechaInicio = $request->input('fecha_inicio');
                $fechaFin = $request->input('fecha_fin');
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }

            // Paginación
            $perPage = $request->input('per_page', 10);
            $reportes = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
            return response()->json([
                'message' => 'Error al obtener los reportes: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'photo' => 'required|string', // Base64 string
            'description' => 'required|string',
            'idUsuario' => 'required|exists:usuarios,idUsuario',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            // Verificar si el usuario tiene reportes pendientes
            $userId = $request->input('idUsuario');
            $validation = ReportValidations::validateUserCanReport($userId);
            
            if (!$validation['can_report']) {
                return response()->json([
                    'message' => $validation['message']
                ], 422);
            }

            // Procesar la imagen
            $photoData = $request->input('photo');
            $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
            $photoData = str_replace(' ', '+', $photoData);
            $image = base64_decode($photoData);

            if ($image === false) {
                return response()->json([
                    'message' => 'Error al decodificar la imagen'
                ], 400);
            }

            // Generar nombre único para la imagen
            $imageName = 'report_' . Str::random(10) . '.jpg';
            $imagePath = "usuarios/{$userId}/reportes/{$imageName}";

            // Guardar la imagen en storage
            Storage::disk('public')->put($imagePath, $image);

            // Crear el reporte
            $report = Report::create([
                'idUsuario' => $userId,
                'description' => $request->input('description'),
                'image_url' => Storage::url($imagePath),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'status' => 0, // Pendiente por defecto
                'fecha' => Carbon::now()->toDateString(),
                'hora' => Carbon::now()->toTimeString(),
            ]);

            // Generar enlace simbólico para la URL requerida
            $symbolicLinkPath = public_path("usuarios/{$userId}/reportes/{$report->id}/imagen");
            $targetPath = storage_path("app/public/{$imagePath}");

            // Crear directorios si no existen
            if (!file_exists(dirname($symbolicLinkPath))) {
                mkdir(dirname($symbolicLinkPath), 0755, true);
            }

            // Crear enlace simbólico
            symlink($targetPath, $symbolicLinkPath);

            return response()->json([
                'message' => 'Reporte creado exitosamente',
                'report' => $report,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:1,2,3', // Aceptado (1), Resuelto (2), Rechazado (3)
            ]);

            $report = Report::findOrFail($id);
            $report->update([
                'status' => $request->input('status'),
            ]);

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