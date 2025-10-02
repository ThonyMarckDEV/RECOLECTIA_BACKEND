<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PerCapitaRecord;
use App\Models\Report;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{

     /**
     * Obtener un resumen del total de basura registrada (per capita).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerCapitaSummary()
    {
        try {
            // Suma del peso para el día de hoy
            $dailyTotal = PerCapitaRecord::whereDate('record_date', Carbon::today())->sum('weight_kg');

            // Suma del peso para la semana actual (de Lunes a Domingo)
            $weeklyTotal = PerCapitaRecord::whereBetween('record_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('weight_kg');
            
            // Suma del peso para el mes actual
            $monthlyTotal = PerCapitaRecord::whereYear('record_date', Carbon::now()->year)
                                          ->whereMonth('record_date', Carbon::now()->month)
                                          ->sum('weight_kg');

            return response()->json([
                'success' => true,
                'data' => [
                    // Convertimos a float para evitar que devuelva 'null' si no hay registros
                    'dailyTotal' => (float) $dailyTotal,
                    'weeklyTotal' => (float) $weeklyTotal,
                    'monthlyTotal' => (float) $monthlyTotal,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen per capita: ' . $e->getMessage(),
            ], 500);
        }
    }

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
            $totalUsers = User::where('idRol', 2)->count();
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