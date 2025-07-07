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
        Schema::create('configuraciones_usuarios', function (Blueprint $table) {
            $table->foreignId('id_user')
                ->constrained('users')
                ->onDelete('cascade')
                ->primary();

            // Nueva columna para el ID del rango predefinido
            $table->foreignId('id_rango_fecha_busqueda') // Usa foreignId para una mejor convención
                ->nullable() // Permite que sea nulo si un usuario no ha guardado una preferencia
                ->constrained('rangos_fechas_busqueda') // Clave foránea a la tabla 'rangos_fechas_busqueda'
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_usuarios');
    }
};
