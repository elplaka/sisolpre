<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTiposPropiedadesTable extends Migration
{
    public function up()
    {
        Schema::create('tipos_propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 15);
            $table->boolean('activo');
            $table->timestamps();
        });

        // Insertar registros iniciales
        DB::table('tipos_propiedades')->insert([
            ['nombre' => 'PREDIO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'INMUEBLE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tipos_propiedades');
    }
}