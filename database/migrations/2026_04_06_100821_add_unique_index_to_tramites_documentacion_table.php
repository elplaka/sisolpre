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
        Schema::table('tramites_documentacion', function (Blueprint $table) {
            // Agregamos el índice único compuesto
            $table->unique(['id_tramite', 'id_requisito_documentacion'], 'uidx_tramite_requisito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites_documentacion', function (Blueprint $table) {
            // Eliminamos el índice por su nombre si revertimos la migración
            $table->dropUnique('uidx_tramite_requisito');
        });
    }
};
