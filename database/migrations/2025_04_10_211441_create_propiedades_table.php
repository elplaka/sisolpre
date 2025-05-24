<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropiedadesTable extends Migration
{
    public function up()
    {
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo')->nullable()->constrained('tipos_propiedades')->onDelete('cascade');
            $table->string('clave_catastral', 18)->nullable();
            $table->string('calle', 35)->nullable();
            $table->string('numero', 8)->nullable();
            $table->foreignId('id_colonia')->nullable()->constrained('colonias')->onDelete('cascade');
            $table->foreignId('id_localidad')->nullable()->constrained('localidades')->onDelete('cascade');
            $table->decimal('superficie', 10, 2)->nullable();
            $table->decimal('superficie_construccion', 10, 2)->nullable();
            $table->string('img_croquis', 35)->nullable();
            $table->foreignId('id_propietario')->nullable()->constrained('personas')->onDelete('cascade');
            $table->boolean('activa')->default(true);
            $table->boolean('editable')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('propiedades');
    }
}