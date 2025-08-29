<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importar la fachada DB

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        // Crea la tabla 'sectores_tramites'
        Schema::create('sectores_tramites', function (Blueprint $table) {
            $table->id(); // Columna auto-incremental para el ID
            $table->string('nombre')->unique(); // Columna para el nombre del sector, debe ser único
            $table->timestamps(); // Columnas created_at y updated_at
        });

        // Inserta los registros iniciales 'PRIVADO' y 'PÚBLICO'
        DB::table('sectores_tramites')->insert([
            ['nombre' => 'PRIVADO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PÚBLICO', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        // Elimina la tabla 'sectores_tramites' si se revierte la migración
        Schema::dropIfExists('sectores_tramites');
    }
};