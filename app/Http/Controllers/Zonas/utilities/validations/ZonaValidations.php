<?php

namespace App\Http\Controllers\Zonas\utilities\validations;

use Illuminate\Validation\Rule;

class ZonaValidations
{
    /**
     * Devuelve las reglas y mensajes para crear una nueva Zona.
     *
     * @return array
     */
    public static function store(): array
    {
        return [
            'rules' => [
                // El campo 'name' del request se mapea a la columna 'nombre' de la BD.
                'name' => 'required|string|unique:zonas,nombre|max:255',
                'description' => 'nullable|string',
            ],
            'messages' => [
                'name.required' => 'El nombre de la zona es obligatorio.',
                'name.unique'   => 'Ya existe una zona con este nombre.',
                'name.max'      => 'El nombre no puede exceder los 255 caracteres.',
            ]
        ];
    }

    /**
     * Devuelve las reglas y mensajes para actualizar una Zona existente.
     *
     * @param int $id El ID de la zona que se está actualizando.
     * @return array
     */
    public static function update(int $id): array
    {
        return [
            'rules' => [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    // Asegura que el nombre sea único, ignorando la zona actual.
                    Rule::unique('zonas', 'nombre')->ignore($id)
                ],
                'description' => 'nullable|string',
            ],
            'messages' => [
                'name.required' => 'El nombre de la zona es obligatorio.',
                'name.unique'   => 'Ya existe otra zona con este nombre.',
                'name.max'      => 'El nombre no puede exceder los 255 caracteres.',
            ]
        ];
    }
}