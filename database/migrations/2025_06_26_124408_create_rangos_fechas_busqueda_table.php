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
        Schema::create('rangos_fechas_busqueda', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('codigo')->unique();
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        DB::table('rangos_fechas_busqueda')->insert([
            ['id' => 1, 'nombre' => 'Hoy', 'codigo' => 'hoy', 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'Sem. Actual', 'codigo' => 'semana_actual', 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'Mes Actual', 'codigo' => 'mes_actual', 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'Año Actual', 'codigo' => 'ano_actual', 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'Sem. Pasada', 'codigo' => 'semana_pasada', 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'Mes Pasado', 'codigo' => 'mes_pasado', 'orden' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'nombre' => 'Año Pasado', 'codigo' => 'ano_pasado', 'orden' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'nombre' => 'Últ. Semana', 'codigo' => 'ult_semana', 'orden' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'nombre' => 'Últ. Mes', 'codigo' => 'ult_mes', 'orden' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'nombre' => 'Últ. Año', 'codigo' => 'ult_ano', 'orden' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 99, 'nombre' => 'Custom', 'codigo' => 'custom', 'orden' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rangos_fechas_busqueda');
    }
};
