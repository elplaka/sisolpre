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
        Schema::table('solicitud_referencias', function (Blueprint $table) {
            // Añade una nueva columna para la llave foránea a 'localidades'.
            // Usamos unsignedBigInteger para que coincida con el tipo de 'id' en 'localidades'.
            $table->unsignedBigInteger('id_localidad')->after('id_solicitud')->nullable();

            // Añade una nueva columna para la llave foránea a 'tipos_propiedades'.
            // foreignId es la forma preferida de Laravel para crear la columna y la restricción.
            $table->foreignId('id_tipo_propiedad')->after('id_localidad')->nullable()->constrained('tipos_propiedades');

            // Define la llave foránea para la tabla 'localidades'
            $table->foreign('id_localidad')
                  ->references('id')
                  ->on('localidades')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_referencias', function (Blueprint $table) {
            // Elimina las llaves foráneas primero. El nombre de la restricción es el predeterminado de Laravel.
            $table->dropForeign(['id_localidad']);
            $table->dropForeign(['id_tipo_propiedad']);
            
            // Elimina las columnas una vez que las restricciones han sido eliminadas.
            $table->dropColumn('id_localidad');
            $table->dropColumn('id_tipo_propiedad');
        });
    }
};