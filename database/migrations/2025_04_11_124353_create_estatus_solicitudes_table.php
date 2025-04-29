<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEstatusSolicitudesTable extends Migration
{
    public function up()
    {
        Schema::create('estatus_solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('nombre', 25);
            $table->boolean('activo');
            $table->string('color', 20);
            $table->timestamps();
        });

        DB::table('estatus_solicitudes')->insert([
            ['id' => 1, 'nombre' => 'RECIBIDA', 'activo' => true, 'color' => 'gray', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'EN FORMA', 'activo' => true, 'color' => 'dodgerblue', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'EN REVISIÓN', 'activo' => true, 'color' => 'violet', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'POR SUBSANAR', 'activo' => true, 'color' => 'slateblue', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'DOCUMENTACIÓN INCOMPLETA', 'activo' => true, 'color' => 'orange', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'RECHAZADA', 'activo' => true, 'color' => 'tomato', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 99, 'nombre' => 'TRÁMITE CONCLUIDO', 'activo' => true, 'color' => 'mediumseagreen', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('estatus_solicitudes');
    }
}