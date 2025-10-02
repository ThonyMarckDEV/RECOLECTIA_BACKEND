<?php

namespace App\Http\Controllers\PerCapita\utilities\validations;

use App\Models\PerCapitaRecord;
use Carbon\Carbon;

class PerCapitaValidations
{
    public static function store(): array
    {
        return [
            'rules' => [
                'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:100'],
                'idUsuario'   => [
                    'required',
                    'exists:usuarios,idUsuario',
                    // Regla personalizada: El usuario no puede registrar dos veces el mismo día.
                    function ($attribute, $value, $fail) {
                        $alreadyExists = PerCapitaRecord::where('idUsuario', $value)
                            ->whereDate('record_date', Carbon::today())
                            ->exists();

                        if ($alreadyExists) {
                            $fail('Ya has registrado el peso de tu basura hoy. Vuelve a intentarlo mañana.');
                        }
                    }
                ],
            ],
            'messages' => [
                'weight_kg.required' => 'Debes ingresar el peso de tu basura.',
                'weight_kg.numeric'  => 'El peso debe ser un número.',
                'weight_kg.min'      => 'El peso debe ser mayor a cero.',
            ]
        ];
    }
}