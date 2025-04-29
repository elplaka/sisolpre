<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesTramitesTable extends Migration
{
    public function up()
    {
        Schema::create('solicitudes_tramites', function (Blueprint $table) {
            $table->foreignId('id_solicitud')->constrained('solicitudes')->onDelete('cascade');
            $table->foreignId('id_tramite')->constrained('catalogo_tramites')->onDelete('cascade');
            $table->timestamps();

            // Llave compuesta
            $table->primary(['id_solicitud', 'id_tramite']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitudes_tramites');
    }
}