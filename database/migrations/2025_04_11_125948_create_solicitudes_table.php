<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesTable extends Migration
{
    public function up()
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_solicitante')->constrained('solicitantes')->onDelete('cascade');
            $table->foreignId('id_propietario')->constrained('solicitantes')->onDelete('cascade');
            $table->foreignId('id_propiedad')->nullable()->constrained('propiedades')->onDelete('cascade');
            $table->foreignId('id_estatus')->default(1)->constrained('estatus_solicitudes')->onDelete('cascade');
            $table->foreignId('id_destino_obra')->nullable()->constrained('destinos_obras')->onDelete('cascade');
            $table->date('fecha_ingreso');
            $table->string('img_croquis', 35)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitudes');
    }
}
