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
        Schema::table('solicitudes', function (Blueprint $table) {
            // Obtiene el ID del sector 'PRIVADO' para usarlo como valor por defecto.
            // Asumimos que 'PRIVADO' será insertado primero y tendrá el ID 1,
            // pero es más seguro consultarlo directamente.
            $privadoSectorId = DB::table('sectores_tramites')
                                 ->where('nombre', 'PRIVADO')
                                 ->value('id');

            // Añade la columna 'id_sector' como clave foránea
            $table->foreignId('id_sector')
                  ->default($privadoSectorId) // Establece el valor por defecto a 'PRIVADO'
                  ->constrained('sectores_tramites') // Define la restricción de clave foránea
                  ->onDelete('cascade')
                  ->after('id_destino_obra'); // Comportamiento al eliminar un sector: elimina la solicitud relacionada
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropForeign(['id_sector']);
            $table->dropColumn('id_sector');
        });
    }
};