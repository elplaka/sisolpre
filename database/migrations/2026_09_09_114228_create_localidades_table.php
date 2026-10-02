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
        Schema::create('localidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->timestamps();
        });

        // Inserción de las localidades
        DB::table('localidades')->insert([
            ['nombre' => 'CONCORDIA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MESILLAS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MALPICA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'AGUACALIENTE DE GÁRATE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL HUAJOTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA EMBOCADA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'ZAVALA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL VERDE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA CONCEPCIÓN', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TEPUXTA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CERRITOS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA PETACA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL PALMITO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA GUAYANERA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'POTRERILLOS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA MESA DEL CARRIZAL', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA CAPILLA DEL TAXTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SANTA LUCÍA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LAS CAÑITAS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SANTA RITA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LOBERAS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA GUÁSIMA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL MAGISTRAL', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CHUPADEROS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'COPALA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL HABAL DE COPALA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA PASTORÍA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PIEDRA BLANCA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL BATEL', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LOS CIRUELOS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SAN LORENZO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EL CUATANTAL', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
