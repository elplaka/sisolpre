<?php

namespace App\Console\Commands;

use App\Models\Propiedad;
use Illuminate\Console\Command;

class UpdatePropiedadDates extends Command
{
    protected $signature = 'propiedades:update-dates';
    protected $description = 'Updates the fecha_aceptacion field for existing properties based on their updated_at timestamp.';

    public function handle()
    {
        $this->info('Starting to update propiedad dates...');

        // Find all records where fecha_activacion is null
        $properties = Propiedad::get();

        $updatedCount = 0;
        foreach ($properties as $property) {
            // Assign the value of updated_at to fecha_activacion
            $property->fecha_aceptacion = $property->created_at;
            $property->save();
            $updatedCount++;
        }

        $this->info("Finished updating {$updatedCount} properties.");
    }
}