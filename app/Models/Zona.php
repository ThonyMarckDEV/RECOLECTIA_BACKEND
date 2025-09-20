<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Relación con recolectores
    public function recolectores()
    {
        return $this->hasMany(User::class, 'idZona' , 'idZona');
    }

    // Relación con usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class, 'idZona' , 'idZona');
    }
}
