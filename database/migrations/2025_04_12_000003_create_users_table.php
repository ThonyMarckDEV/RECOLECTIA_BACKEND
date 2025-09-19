<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('idUsuario');
            $table->string('username')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('perfil')->nullable()->comment('URL de la foto de perfil de Google');
            $table->integer('recolectPoints')->default(0)->comment('Puntos de recolección del usuario');
            $table->string('password')->nullable();
            $table->unsignedBigInteger('idRol')->default(2); // Por defecto 2 que es cliente
            $table->boolean('estado')->default(1)->comment('1: Activo, 0: Inactivo');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('idRol')->references('idRol')->on('roles')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};