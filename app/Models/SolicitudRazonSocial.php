<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudRazonSocial extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_razones_sociales';

    protected $fillable = [
        'id_solicitud',
        'nombre',
    ];

    /**
     * Get the solicitud that owns the referencia.
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
