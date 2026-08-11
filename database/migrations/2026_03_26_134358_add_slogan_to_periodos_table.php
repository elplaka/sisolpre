<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importante añadir esto

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregamos la columna
        Schema::table('periodos', function (Blueprint $table) {
            $table->text('slogan')->nullable()->after('fin');
        });

        // 2. Insertamos el valor en el periodo con ID = 1
        DB::table('periodos')
            ->where('id', 1)
            ->update([
                'slogan' => 'Con la Fuerza del Pueblo... Concordia para Todos',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodos', function (Blueprint $table) {
            $table->dropColumn('slogan');
        });
    }
};
