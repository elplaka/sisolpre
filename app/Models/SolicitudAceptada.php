<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAceptada extends Model
{
    use HasFactory;

     protected $table = 'solicitudes_aceptadas';

    protected $fillable = [
        'activa',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
