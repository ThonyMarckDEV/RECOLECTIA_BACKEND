<?php

namespace App\Http\Controllers\Reports;

use App\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReportController extends Controller
{
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
            // Decodificar la imagen base64
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
            $userId = $request->input('idUsuario');
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
}