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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Inserta el periodo_actual solo si no existe
        DB::table('settings')->insertOrIgnore([
            'key' => 'periodo_actual',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
