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
        Schema::create('croquis_aux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_solicitud')->unique()->constrained('solicitudes')->onDelete('cascade');
            $table->string('img', 35)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('croquis_aux');
    }
};
