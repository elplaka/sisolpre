<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importar la fachada DB

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Obtiene el ID del sector 'PRIVADO' para usarlo como valor por defecto.
            // Asumimos que 'PRIVADO' será insertado primero y tendrá el ID 1,
            // pero es más seguro consultarlo directamente.
            $privadoSectorId = DB::table('sectores_tramites')
                                 ->where('nombre', 'PRIVADO')
                                 ->value('id');

            // Añade la columna 'id_sector' como clave foránea
            $table->foreignId('id_sector')
                  ->default($privadoSectorId) // Establece el valor por defecto a 'PRIVADO'
                  ->constrained('sectores_tramites') // Define la restricción de clave foránea
                  ->onDelete('cascade')
                  ->after('id_destino_obra'); // Comportamiento al eliminar un sector: elimina la solicitud relacionada
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Verifica si la clave foránea existe antes de intentar eliminarla
            // Esto es útil si la migración 'up' no se completó del todo
            if (Schema::hasColumn('solicitudes', 'id_sector')) {
                // Si la columna existe, intenta eliminar la clave foránea
                // El nombre de la clave foránea por defecto es table_column_foreign
                // Por ejemplo, 'solicitudes_id_sector_foreign'
                // Puedes verificar el nombre exacto con SHOW CREATE TABLE solicitudes;
                $foreignKeyExists = false;
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('solicitudes');
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getLocalColumns()[0] === 'id_sector' && $foreignKey->getForeignTableName() === 'sectores_tramites') {
                        $table->dropForeign([$foreignKey->getName()]);
                        $foreignKeyExists = true;
                        break;
                    }
                }
                // Si la clave foránea no se encontró o se eliminó, procede a eliminar la columna
                if (Schema::hasColumn('solicitudes', 'id_sector')) {
                    $table->dropColumn('id_sector');
                }
            }
        });
    }
};