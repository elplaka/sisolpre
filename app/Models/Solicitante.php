<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solicitante extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos (opcional si sigue la convención en plural "solicitantes")
    protected $table = 'solicitantes';

    // Campos permitidos para asignación masiva
    protected $fillable = [
        'curp',
        'nombre',
        'apellidos',
        'numero_telefonico',
        'id_localidad',
    ];

    /**
     * Relación: Un solicitante pertenece a una localidad.
     */
    public function localidad(): BelongsTo
    {
        return $this->belongsTo(Localidad::class, 'id_localidad');
    }

    /**
     * Relación opcional: Un solicitante puede tener muchas solicitudes.
     * (Descomenta esto si tu tabla solicitudes apunta a este modelo)
     */
    /*
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'solicitante_id');
    }
    */
}
