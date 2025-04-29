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
        if (!Schema::hasTable('colonias')) {
            Schema::create('colonias', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 25);
                $table->timestamps();
            });
        }

         // Insertar la colonia CENTRO
         DB::table('colonias')->insert([
            'nombre' => 'CENTRO',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colonias');
    }
};
