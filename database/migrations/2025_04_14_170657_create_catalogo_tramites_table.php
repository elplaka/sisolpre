<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCatalogoTramitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalogo_tramites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo')->constrained('tipos_tramites')->onDelete('cascade');
            $table->string('nombre', 40);
            $table->string('nombre_abreviado', 20);
            $table->boolean('activo');
            $table->timestamps();
        });

        DB::table('catalogo_tramites')->insert([
            ['id_tipo' => 1, 'nombre' => 'CONSTANCIA DE ZONIFICACIÓN', 'nombre_abreviado' => 'CONST. ZONIF.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 1, 'nombre' => 'CONSTANCIA DE USO DE SUELO', 'nombre_abreviado' => 'CONST. USO SUELO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 1, 'nombre' => 'CONSTANCIA DE ALINEAMIENTO', 'nombre_abreviado' => 'CONST. ALINEAM.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 1, 'nombre' => 'CONSTANCIA DE NÚMERO OFICIAL', 'nombre_abreviado' => 'CONST. NUM. OFI.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'OBRA NUEVA', 'nombre_abreviado' => 'OBRA NVA.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'AMPLIACIÓN DE OBRA', 'nombre_abreviado' => 'AMPL. OBRA', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'CAMBIO DE USO', 'nombre_abreviado' => 'CAMB. USO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'REPARACIÓN O REMODELACIÓN', 'nombre_abreviado' => 'REPAR. O REMOD.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'DEMOLICIÓN', 'nombre_abreviado' => 'DEMOLICIÓN', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 2, 'nombre' => 'BARDAS O CERCAS', 'nombre_abreviado' => 'BARDAS O CERCAS', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 3, 'nombre' => 'ROTURA O CORTE DE PAVIMENTO', 'nombre_abreviado' => 'ROTU. O CORTE PAV.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 3, 'nombre' => 'EXCAVACIÓN O RELLENO', 'nombre_abreviado' => 'EXCAV. O RELLENO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 3, 'nombre' => 'RAMPAS, BANQUETAS Y GUARNICIONES', 'nombre_abreviado' => 'RAMP. BANQ. GUARN.', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id_tipo' => 3, 'nombre' => 'OCUPACIÓN TEMPORAL', 'nombre_abreviado' => 'OCUP. TEMPORAL', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tramites');
    }
}
