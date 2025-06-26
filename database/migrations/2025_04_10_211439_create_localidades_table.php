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
            $table->unsignedBigInteger('id')->primary();
            $table->string('nombre', 25);
            $table->timestamps();
        });

        DB::table('localidades')->insert([
            ['id' => 0, 'nombre' => 'CONCORDIA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1, 'nombre' => 'MESILLAS', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'MALPICA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'LA EMBOCADA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'EL HUAJOTE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'nombre' => 'ZAVALA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'nombre' => 'EL VERDE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'nombre' => 'AGUACALIENTE DE GÁRATE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'nombre' => 'CHUPADEROS', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'nombre' => 'EL VERDE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'nombre' => 'LA CONCEPCIÓN', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 50, 'nombre' => 'TEPUXTA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 51, 'nombre' => 'CERRITOS', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 71, 'nombre' => 'COPALA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 77, 'nombre' => 'LA COLONIA (CHIRIMOYOS)', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 80, 'nombre' => 'TAMBÁ', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'nombre' => 'EL PALMITO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 83, 'nombre' => 'SANTA CATARINA', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // DB::statement('ALTER TABLE localidades AUTO_INCREMENT = 12');
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
