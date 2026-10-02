<?php

use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('rangos_fechas_busqueda')->insert([
            ['id' => 11, 'nombre' => 'Año Gob #1', 'codigo' => 'ano_gob_1', 'orden' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'nombre' => 'Año Gob #2', 'codigo' => 'ano_gob_2', 'orden' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'nombre' => 'Año Gob #3', 'codigo' => 'ano_gob_3', 'orden' => 13, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rangos_fechas_busqueda')
            ->whereIn('id', [11, 12, 13])
            ->delete();
    }
};
