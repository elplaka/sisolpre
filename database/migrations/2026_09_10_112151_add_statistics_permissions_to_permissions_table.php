<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission; // Make sure to import the Permission model

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define the permissions to add
        $permissions = [
            'ver_estadisticas',
            'editar_estadisticas',
        ];

        // Get the default guard name from the permission config
        $guardName = config('auth.defaults.guard');

        foreach ($permissions as $permissionName) {
            // Create the permission if it doesn't already exist
            // This prevents errors if you run the migration multiple times
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => $guardName]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define the permissions to remove
        $permissions = [
            'ver_estadisticas',
            'editar_estadisticas',
        ];

        // Delete the permissions
        Permission::whereIn('name', $permissions)->delete();
    }
};