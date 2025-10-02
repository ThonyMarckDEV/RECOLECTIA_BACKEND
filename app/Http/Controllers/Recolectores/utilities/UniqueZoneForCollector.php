<?php

namespace App\Http\Controllers\Recolectores\utilities;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueZoneForCollector implements Rule
{
    /**
     * El ID del usuario a ignorar durante la validación (para actualizaciones).
     * @var int|null
     */
    protected $ignoreUserId;

    /**
     * Crea una nueva instancia de la regla.
     */
    public function __construct($ignoreUserId = null)
    {
        $this->ignoreUserId = $ignoreUserId;
    }

    /**
     * Determina si la regla de validación pasa.
     *
     * @param  string  $attribute
     * @param  mixed  $value El valor del campo (el ID de la zona).
     * @return bool Devuelve true si la validación pasa, false si falla.
     */
    public function passes($attribute, $value)
    {
        // Construimos la consulta base.
        $query = User::where('idZona', $value)->where('idRol', 3);

        // Si estamos actualizando, ignoramos al usuario actual.
        if ($this->ignoreUserId) {
            $query->where('idUsuario', '!=', $this->ignoreUserId);
        }

        // La validación PASA si NO existe otro recolector en esa zona.
        // Por eso retornamos la negación de exists().
        return !$query->exists();
    }

    /**
     * Obtiene el mensaje de error de la validación.
     * Este método se llama solo si passes() devuelve false.
     *
     * @return string
     */
    public function message()
    {
        return 'La zona seleccionada ya está asignada a otro recolector.';
    }
}