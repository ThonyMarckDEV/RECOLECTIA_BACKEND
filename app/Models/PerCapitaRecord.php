<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerCapitaRecord extends Model
{
    use HasFactory;

    protected $table = 'per_capita_records';

    protected $fillable = [
        'idUsuario',
        'weight_kg',
        'record_date',
    ];

    /**
     * Un registro pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}