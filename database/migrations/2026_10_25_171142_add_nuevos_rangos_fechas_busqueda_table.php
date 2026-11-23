<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Desactivamos restricciones de llaves foráneas temporalmente por seguridad
        Schema::disableForeignKeyConstraints();

        // Aseguramos que la tabla exista antes de insertar
        if (Schema::hasTable('rangos_fechas_busqueda')) {
            // Usamos updateOrInsert para actualizar si ya existe el ID o insertarlo si no
            $nuevosRegistros = [
                ['id' => 6, 'nombre' => 'Semana Actual', 'codigo' => 'semana_actual', 'orden' => 6],
                ['id' => 7, 'nombre' => 'Semana Anterior', 'codigo' => 'semana_anterior', 'orden' => 7],
                ['id' => 8, 'nombre' => 'Mes Actual', 'codigo' => 'mes_actual', 'orden' => 8],
                ['id' => 9, 'nombre' => 'Mes Anterior', 'codigo' => 'mes_anterior', 'orden' => 9],
            ];

            foreach ($nuevosRegistros as $registro) {
                DB::table('rangos_fechas_busqueda')->updateOrInsert(
                    ['id' => $registro['id']],
                    [
                        'nombre' => $registro['nombre'],
                        'codigo' => $registro['codigo'],
                        'orden' => $registro['orden'],
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())')
                    ]
                );
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Revertimos borrando los IDs que acabamos de agregar
        DB::table('rangos_fechas_busqueda')->whereIn('id', [6, 7, 8, 9])->delete();

        Schema::enableForeignKeyConstraints();
    }
};
