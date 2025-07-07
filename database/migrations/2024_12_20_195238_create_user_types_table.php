<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importa DB para usar consultas directas

class CreateUserTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('user_types')->insert([
            ['name' => 'ADMINISTRADOR', 'description' => 'Usuario con todos los permisos'],
            ['name' => 'DIRECTOR', 'description' => 'Usuario con permisos de auditoría y administración'],
            ['name' => 'AUDITOR', 'description' => 'Usuario con permisos de auditoría'],
            ['name' => 'AUXILIAR', 'description' => 'Usuario con permisos de auxiliar de auditoría'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_types');
    }
}

