<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescripcionToCatalogoTramitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('catalogo_tramites', function (Blueprint $table) {
            // Creamos el campo descripcion, permitiendo nulos
            // 'after' lo coloca después de 'nombre' en la estructura de MySQL
            $table->text('descripcion')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('catalogo_tramites', function (Blueprint $table) {
            // Es vital definir el rollback para poder revertir si es necesario
            $table->dropColumn('descripcion');
        });
    }
}