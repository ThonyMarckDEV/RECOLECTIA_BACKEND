<?php

namespace App\Http\Controllers\Locations;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;

class LocationsController extends Controller
{
    /**
     * Update or insert user location.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Get the authenticated user's ID
        $idUsuario = Auth::id();

        // Update or create the location record
        Location::updateOrCreate(
            ['idUsuario' => $idUsuario], // Search criteria: match by idUsuario
            [                            // Attributes to update or create
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        return response()->json([
            'message' => 'Ubicación actualizada exitosamente',
        ], 200);
    }

   /**
     * Get the collector's location in the same zone as the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollectorLocation()
    {
        $user = Auth::user();
        $idZona = $user->idZona;

        if (!$idZona) {
            return response()->json([
                'message' => 'El usuario no tiene una zona asignada',
            ], 400);
        }

        $collector = Location::join('usuarios', 'locations.idUsuario', '=', 'usuarios.idUsuario')
            ->join('roles', 'usuarios.idRol', '=', 'roles.idRol')
            ->where('roles.nombre', 'recolector')
            ->where('usuarios.idZona', $idZona)
            ->select('locations.latitude', 'locations.longitude')
            ->first();

        if (!$collector) {
            return response()->json([
                'message' => 'No se encontró la ubicación del recolector en esta zona',
            ], 404);
        }

        return response()->json([
            'latitude' => $collector->latitude,
            'longitude' => $collector->longitude,
        ], 200);
    }
}