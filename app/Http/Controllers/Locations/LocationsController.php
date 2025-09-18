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

        // Assume user is authenticated, get idUsuario from authenticated user
        $idUsuario = Auth::id(); // Adjust based on your auth system

        // Insert new location record
        Location::updateOrCreate([
            'idUsuario' => $idUsuario,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Ubicación actualizada exitosamente',
        ], 200);
    }
}