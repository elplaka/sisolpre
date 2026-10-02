<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla principal de solicitudes
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');

            // Relaciones foráneas
            $table->foreignId('id_solicitante')->constrained('solicitantes')->onDelete('cascade');
            $table->foreignId('id_area')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('id_estatus')->constrained('estatus_solicitudes')->onDelete('cascade');

            // Relación con el usuario autenticado (User)
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');

            $table->string('peticion', 255);
            $table->decimal('cantidad_aprobada', 10, 2)->nullable();
            $table->string('observaciones', 100)->nullable();
            $table->timestamps();
        });

        // 2. Tabla de historial para auditoría (altas y cambios)
        Schema::create('solicitudes_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade'); // Quién hizo el cambio/alta
            $table->string('accion'); // Ej: 'CREACION', 'ACTUALIZACION', 'ELIMINACION'
            $table->json('datos_anteriores')->nullable(); // Los datos antes del cambio
            $table->json('datos_nuevos')->nullable();     // Los datos después del cambio / creación
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_historial');
        Schema::dropIfExists('solicitudes');
    }
};
