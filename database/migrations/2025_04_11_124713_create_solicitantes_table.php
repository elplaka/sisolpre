<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitantesTable extends Migration
{
    public function up()
    {
        Schema::create('solicitantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_persona')->constrained('personas')->onDelete('cascade');
            $table->string('calle', 60)->nullable();
            $table->foreignId('id_colonia')->nullable()->constrained('colonias')->onDelete('cascade');
            $table->string('num_casa', 10)->nullable();
            $table->foreignId('id_localidad')->nullable()->constrained('localidades')->onDelete('cascade');
            $table->string('telefono', 10)->nullable();
            $table->string('email', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitantes');
    }
}

