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
        Schema::table('requisitos_documentacion', function (Blueprint $table) {
            // Agregamos el campo descripcion
            // Usamos nullable() para que por defecto sea NULL
            // Usamos after('nombre') para que en MySQL aparezca después de esa columna
            $table->text('descripcion')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitos_documentacion', function (Blueprint $table) {
            // Es indispensable definir cómo revertir el cambio
            $table->dropColumn('descripcion');
        });
    }
};