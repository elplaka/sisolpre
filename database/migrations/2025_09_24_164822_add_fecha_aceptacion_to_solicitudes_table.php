<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // 1. Add the new column 'fecha_aceptacion' as a nullable DATE.
            // Use 'date()' for a date (YYYY-MM-DD) without time.
            $table->date('fecha_aceptacion')->nullable();
            
            // 2. Add the column for the foreign key.
            $table->unsignedBigInteger('folio')->nullable()->after('fecha_aceptacion');

            // 3. Define the foreign key constraint.
            $table->foreign('folio', 'custom_folio_fk')
                ->references('id')
                ->on('solicitudes_aceptadas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // 1. Drop the foreign key first.
            $table->dropForeign('custom_folio_fk');
            
            // 2. Then, drop the 'folio' and 'fecha_aceptacion' columns.
            $table->dropColumn(['folio', 'fecha_aceptacion']);
        });
    }
};