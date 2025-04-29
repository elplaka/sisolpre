<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCelularGeneroToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('celular')->nullable()->after('email');
            $table->enum('genero', ['H', 'M'])->default('H')->after('celular');
            $table->boolean('verificado')->default(false); // Campo con valor predeterminado false
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('celular');
            $table->dropColumn('genero');
            $table->dropColumn('verificado');
        });
    }
}

