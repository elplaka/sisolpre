<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla domicilios_notificaciones.
     */
    public function up(): void
    {
        Schema::create('domicilios_notificaciones', function (Blueprint $table) {
            $table->id();    

            // Campos del domicilio
            $table->string('direccion', 100);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la tabla domicilios_notificaciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('domicilios_notificaciones');
    }
};