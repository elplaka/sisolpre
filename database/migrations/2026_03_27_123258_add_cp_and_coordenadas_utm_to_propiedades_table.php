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
        Schema::table('propiedades', function (Blueprint $table) {
            $table->string('codigo_postal', 5)->nullable()->after('id_localidad');

            $table->string('coordenada_utm_x', 15)->nullable()->after('img_croquis');
            $table->string('coordenada_utm_y', 15)->nullable()->after('coordenada_utm_x');
        });
    }

    public function down(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            $table->dropColumn(['codigo_postal', 'coordenada_utm_x', 'coordenada_utm_y']);
        });
    }
};
