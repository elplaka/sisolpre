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
        // 1. Desactivar temporalmente las restricciones de llaves foráneas
        Schema::disableForeignKeyConstraints();

        // 2. Si la tabla NO existe, la creamos
        if (!Schema::hasTable('rangos_fechas_busqueda')) {
            Schema::create('rangos_fechas_busqueda', function (Blueprint $table) {
                $table->id();
                $table->string('nombre')->unique();
                $table->string('codigo')->unique();
                $table->integer('orden')->default(0);
                $table->timestamps();
            });
        } else {
            // Si ya existe, ahora sí podemos truncarla sin problema
            DB::table('rangos_fechas_busqueda')->truncate();
        }

        // 3. Insertamos los nuevos registros limpios
        DB::table('rangos_fechas_busqueda')->insert([
            ['id' => 1, 'nombre' => 'Hoy', 'codigo' => 'hoy', 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'Año Calendario Actual', 'codigo' => 'anio_actual', 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'Año Calendario Anterior', 'codigo' => 'anio_anterior', 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'Año de Gobierno Actual', 'codigo' => 'anio_gobierno_actual', 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'Año de Gobierno Anterior', 'codigo' => 'anio_gobierno_anterior', 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 99, 'nombre' => 'Custom', 'codigo' => 'custom', 'orden' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Volver a activar las restricciones de llaves foráneas
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('rangos_fechas_busqueda');
        Schema::enableForeignKeyConstraints();
    }
};
