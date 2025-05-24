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
            ['id' => 3, 'nombre' => 'MALPICA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'AGUACALIENTE DE GÁRATE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'EL HUAJOTE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'LA EMBOCADA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'nombre' => 'ZAVALA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'nombre' => 'EL VERDE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'nombre' => 'LA CONCEPCIÓN', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'nombre' => 'TEPUXTA', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'nombre' => 'CERRITOS', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('ALTER TABLE localidades AUTO_INCREMENT = 12');
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
