<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Obtener métricas para el dashboard del administrador
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardMetrics()
    {
        try {
            // Obtener el rol de recolector
            $collectorRole = Rol::where('nombre', 'recolector')->first();
            $collectorRoleId = $collectorRole ? $collectorRole->idRol : null;

            // Contar métricas
            $totalReports = Report::count();
            $totalUsers = User::count();
            $totalCollectors = $collectorRoleId ? User::where('idRol', $collectorRoleId)->count() : 0;
            $pendingReports = Report::where('status', 0)->count();
            $acceptedReports = Report::where('status', 1)->count();
            $resolvedReports = Report::where('status', 2)->count();
            $rejectedReports = Report::where('status', 3)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'totalReports' => $totalReports,
                    'totalUsers' => $totalUsers,
                    'totalCollectors' => $totalCollectors,
                    'pendingReports' => $pendingReports,
                    'acceptedReports' => $acceptedReports,
                    'resolvedReports' => $resolvedReports,
                    'rejectedReports' => $rejectedReports,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métricas: ' . $e->getMessage(),
            ], 500);
        }
    }
}