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
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->string('años');
            $table->date('inicio');
            $table->date('fin');
            $table->timestamps();
        });

        // Insertar registros iniciales
        DB::table('periodos')->insert([
            [
                'años' => '2024-2027',
                'inicio' => '2024-11-01',
                'fin' => '2027-10-31',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'años' => '2027-2030',
                'inicio' => '2027-11-01',
                'fin' => '2030-10-31',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
