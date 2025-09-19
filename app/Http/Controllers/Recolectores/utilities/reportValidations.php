<?php

namespace App\Http\Controllers\Reports\utilities;

use App\Models\Report;

class ReportValidations
{
    /**
     * Verifica si el usuario tiene un reporte pendiente
     *
     * @param int $userId
     * @return bool
     */
    public static function userHasPendingReport($userId)
    {
        return Report::where('idUsuario', $userId)
                    ->where('status', 0) // 0 = Pendiente
                    ->exists();
    }

    /**
     * Valida si el usuario puede crear un nuevo reporte
     *
     * @param int $userId
     * @return array
     */
    public static function validateUserCanReport($userId)
    {
        if (self::userHasPendingReport($userId)) {
            return [
                'can_report' => false,
                'message' => 'Ya tienes un reporte pendiente. No puedes crear otro hasta que se resuelva el actual.'
            ];
        }

        return [
            'can_report' => true,
            'message' => 'Puedes crear un nuevo reporte.'
        ];
    }
}