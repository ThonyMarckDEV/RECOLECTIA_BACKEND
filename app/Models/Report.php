<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'idUsuario',
        'description',
        'image_url',
        'latitude',
        'longitude',
        'status',
        'fecha',
        'hora',
    ];

    /**
     * Relación con el modelo User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}