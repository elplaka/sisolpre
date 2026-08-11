<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Creamos la columna permitiendo temporalmente valores nulos (null)
        Schema::table('catalogo_tramites', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nombre');
        });

        // 2. MIGRA LOS DATOS ACTUALES: Recuperamos todos los trámites viejos
        $tramites = DB::table('catalogo_tramites')->get();

        foreach ($tramites as $tramite) {
            DB::table('catalogo_tramites')
                ->where('id', $tramite->id)
                ->update([
                    // Convertimos "Constancia de Número Oficial" -> "constancia-de-numero-oficial"
                    'slug' => Str::slug($tramite->nombre)
                ]);
        }

        // 3. Modificamos la columna para que ya sea obligatoria (NOT NULL) y ÚNICA
        Schema::table('catalogo_tramites', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_tramites', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
