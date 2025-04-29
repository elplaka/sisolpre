<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTiposTramitesTable extends Migration
{
    public function up()
    {
        Schema::create('tipos_tramites', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 25);
            $table->boolean('activo');
            $table->timestamps();
        });

        DB::table('tipos_tramites')->insert([
            ['nombre' => 'DE USO DE SUELO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'DE CONSTRUCCIÓN', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'USO DE LA VÍA PÚBLICA', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tipos_tramites');
    }
}