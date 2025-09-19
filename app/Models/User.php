<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'idUsuario';

    protected $fillable = [
        'username',
        'name',
        'password',
        'perfil',
        'idRol',
        'estado',
        'recolectPoints',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'recolectPoints' => 'integer',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'rol' => $this->rol()->first()->nombre,
            'username' => $this->username,
            'name' => $this->name,
            'perfil' => $this->perfil,
            'recolectPoints' => $this->recolectPoints,
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'idRol', 'idRol');
    }
}