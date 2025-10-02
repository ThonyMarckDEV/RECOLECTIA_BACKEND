<?php

namespace App\Http\Controllers\Reports\utilities;

use App\Models\Report;

class ReportValidations
{
    /**
     * Devuelve las reglas y mensajes para crear un nuevo Reporte.
     *
     * @return array
     */
    public static function store(): array
    {
        return [
            'rules' => [
                'photo'       => ['required', 'string'],
                'description' => ['required', 'string', 'max:1000'],
                'latitude'    => ['required', 'numeric'],
                'longitude'   => ['required', 'numeric'],
                'idUsuario'   => [
                    'required',
                    'exists:usuarios,idUsuario',
                    // Regla personalizada: verifica si el usuario ya tiene un reporte pendiente.
                    // Esto reemplaza la llamada a validateUserCanReport() en el controlador.
                    function ($attribute, $value, $fail) {
                        $hasPending = Report::where('idUsuario', $value)
                                            ->where('status', 0) // 0 = Pendiente
                                            ->exists();

                        if ($hasPending) {
                            $fail('Ya tienes un reporte pendiente. No puedes crear otro hasta que se resuelva.');
                        }
                    }
                ],
            ],
            'messages' => [
                'photo.required'       => 'La evidencia fotográfica es obligatoria.',
                'description.required' => 'La descripción del reporte es obligatoria.',
                'latitude.required'    => 'La latitud es obligatoria.',
                'latitude.numeric'     => 'La latitud debe ser un valor numérico.',
                'longitude.required'   => 'La longitud es obligatoria.',
                'longitude.numeric'    => 'La longitud debe ser un valor numérico.',
                'idUsuario.exists'     => 'El usuario especificado no es válido.',
            ]
        ];
    }

    /**
     * Verifica si el usuario tiene un reporte pendiente (sin cambios)
     */
    public static function userHasPendingReport($userId)
    {
        return Report::where('idUsuario', $userId)
                       ->where('status', 0)
                       ->exists();
    }

    /**
     * Valida si el usuario puede crear un nuevo reporte (sin cambios, aunque ya no se usa directamente en el store)
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