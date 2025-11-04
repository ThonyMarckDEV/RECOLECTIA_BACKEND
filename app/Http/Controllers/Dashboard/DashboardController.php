<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PerCapitaRecord;
use App\Models\Report;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{

/**
     * Obtener un resumen del total de basura registrada (per capita) por rango de fechas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerCapitaSummary(Request $request)
    {
        try {
            // 1. Validar las fechas de entrada
            $validated = $request->validate([
                'startDate' => 'nullable|date_format:Y-m-d',
                'endDate'   => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
            ]);

            // 2. Definir el rango de fechas (default: mes actual si no se envían)
            $startDate = Carbon::parse($validated['startDate'] ?? Carbon::now()->startOfMonth());
            $endDate   = Carbon::parse($validated['endDate'] ?? Carbon::now()->endOfMonth());

            // 3. Consulta principal agrupada por día dentro del rango
            $records = PerCapitaRecord::whereBetween('record_date', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(record_date) as date'), // Formato Y-m-d
                    DB::raw('SUM(weight_kg) as total_weight')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                // Usamos keyBy para buscar fácilmente por fecha
                ->keyBy('date'); 

            // 4. Calcular totales para las tarjetas
            $totalInRange = $records->sum('total_weight');
            $dailyTotal = PerCapitaRecord::whereDate('record_date', Carbon::today())->sum('weight_kg');
            $weeklyTotal = PerCapitaRecord::whereBetween('record_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('weight_kg');

            // 5. Formatear para el gráfico (rellenar días con 0)
            $dateRange = CarbonPeriod::create($startDate, $endDate);
            $chartLabels = [];
            $chartValues = [];

            foreach ($dateRange as $date) {
                $dateString = $date->format('Y-m-d');
                $chartLabels[] = $dateString;
                // Si existe un registro para esa fecha, usa su total, si no, 0
                $chartValues[] = $records->has($dateString) ? (float)$records[$dateString]->total_weight : 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    // Totales para las tarjetas
                    'dailyTotal'   => (float) $dailyTotal,   // Sigue siendo el de "hoy"
                    'weeklyTotal'  => (float) $weeklyTotal,  // Sigue siendo el de "esta semana"
                    'monthlyTotal' => (float) $totalInRange, // "Monthly" ahora es el total del RANGO
                    
                    // Datos para el gráfico dinámico
                    'chart' => [
                        'labels' => $chartLabels, // ['2025-10-01', '2025-10-02', ...]
                        'values' => $chartValues, // [0, 10.00, 8.00, ...]
                    ],
                    
                    // Devolvemos las fechas usadas para confirmación
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate'   => $endDate->format('Y-m-d'),
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'Datos de solicitud inválidos.',
                'errors' => $e->errors(),
            ], 422);
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