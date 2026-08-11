<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
     Schema::create('autoridades_firmantes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 60);
            $table->string('cargo', 60);
            $table->string('titulo', 20);
            $table->boolean('activa')->default(true);
            $table->timestamps();           
        });

        DB::table('autoridades_firmantes')->insert([
            'nombre' => 'JESÚS MARTÍNEZ ZAMUDIO',
            'cargo'  => 'DIRECTOR DE PLANEACIÓN URBANA',
            'titulo' => 'ARQ.',
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autoridades_firmantes');
    }
};
