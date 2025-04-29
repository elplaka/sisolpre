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
            $table->string('nombre', 25);
            $table->timestamps();
        });

        // Insertar las localidades
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
