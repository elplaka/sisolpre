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
        Schema::create('destinos_obras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->boolean('activo');
            $table->timestamps();
        });

        // Insertar registros
        DB::table('destinos_obras')->insert([
            ['nombre' => 'HABITACIONAL', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'COMERCIAL', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SERVICIOS', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'INDUSTRIAL', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinos_obras');
    }
};
