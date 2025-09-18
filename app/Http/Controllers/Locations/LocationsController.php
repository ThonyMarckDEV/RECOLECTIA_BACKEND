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
     * Get the collector's location.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollectorLocation()
    {
        $collector = Location::join('usuarios', 'locations.idUsuario', '=', 'usuarios.idUsuario')
            ->join('roles', 'usuarios.idRol', '=', 'roles.idRol')
            ->where('roles.nombre', 'recolector')
            ->select('locations.latitude', 'locations.longitude')
           // ->orderBy('locations.created_at', 'desc') // Specify table for created_at
            ->first();

        if (!$collector) {
            return response()->json([
                'message' => 'No se encontró la ubicación del recolector',
            ], 404);
        }

        return response()->json([
            'latitude' => $collector->latitude,
            'longitude' => $collector->longitude,
        ], 200);
    }
}