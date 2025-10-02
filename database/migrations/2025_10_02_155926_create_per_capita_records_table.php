<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('per_capita_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idUsuario')->constrained('usuarios', 'idUsuario')->onDelete('cascade');
            $table->decimal('weight_kg', 8, 2); // Peso en kilogramos, ej: 1.25 kg
            $table->date('record_date');
            $table->timestamps();

            // Regla única: Un usuario solo puede tener un registro por día.
            $table->unique(['idUsuario', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_capita_records');
    }
};