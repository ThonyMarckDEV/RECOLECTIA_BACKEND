<?php

// El namespace está bien, no lo toques.
namespace App\Http\Controllers\Recolectores\utilities\validations;


use App\Http\Controllers\Recolectores\utilities\UniqueZoneForCollector;
use Illuminate\Validation\Rule;

// ¡AQUÍ ESTÁ EL CAMBIO! Renombramos la clase.
class RecolectorValidations 
{
    /**
     * Devuelve las reglas y mensajes para crear un nuevo recolector.
     *
     * @return array
     */
    public static function store(): array
    {
        return [
            'rules' => [
                'username' => 'required|string|unique:usuarios,username|max:255',
                'name'     => 'required|string|max:255',
                'password' => 'required|string|min:8',
                'estado'   => 'required|in:0,1',
                'idZona'   => [
                    'required',
                    'integer',
                    'exists:zonas,id',
                    new UniqueZoneForCollector()
                ],
            ],
            'messages' => [
                'username.required' => 'El nombre de usuario es obligatorio.',
                'username.unique'   => 'Este nombre de usuario ya está en uso.',
                'name.required'     => 'El nombre completo es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
                'estado.required'   => 'El estado es obligatorio.',
                'idZona.required'   => 'Debe seleccionar una zona.',
                'idZona.exists'     => 'La zona seleccionada no es válida.',
            ]
        ];
    }

    /**
     * Devuelve las reglas y mensajes para actualizar un recolector existente.
     *
     * @param int $idUsuario El ID del usuario que se está actualizando.
     * @return array
     */
    public static function update(int $idUsuario): array
    {
        return [
            'rules' => [
                'username' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('usuarios', 'username')->ignore($idUsuario, 'idUsuario')
                ],
                'name'     => 'required|string|max:255',
                'password' => 'nullable|string|min:8',
                'estado'   => 'required|in:0,1',
                'idZona'   => [
                    'required',
                    'integer',
                    'exists:zonas,id',
                    new UniqueZoneForCollector($idUsuario)
                ],
            ],
            'messages' => [
                'username.required' => 'El nombre de usuario es obligatorio.',
                'username.unique'   => 'Este nombre de usuario ya está en uso por otra persona.',
                'name.required'     => 'El nombre completo es obligatorio.',
                'password.min'      => 'La nueva contraseña debe tener al menos 8 caracteres.',
                'estado.required'   => 'El estado es obligatorio.',
                'idZona.required'   => 'Debe seleccionar una zona.',
                'idZona.exists'     => 'La zona seleccionada no es válida.',
            ]
        ];
    }
}