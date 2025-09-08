<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            $table->string('calle', 80)->change();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            $table->string('calle', 35)->change();
        });
    }
};