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
        Schema::table('tramites_documentacion', function (Blueprint $table) {
            // Agregamos el ID al principio de la tabla
            $table->id()->first();
        });
    }

    public function down(): void
    {
        Schema::table('tramites_documentacion', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
