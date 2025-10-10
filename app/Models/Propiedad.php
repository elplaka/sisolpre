<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    use HasFactory;

    protected $table = 'propiedades';

    protected $fillable = [
        'id_tipo',
        'clave_catastral',
        'calle',
        'numero',
        'id_colonia',
        'id_localidad',
        'superficie',
        'superficie_construccion',
        'id_contacto',
        'img_croquis'
    ];

    public function colonia()
    {
        return $this->belongsTo(Colonia::class, 'id_colonia');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'id_localidad');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoPropiedad::class, 'id_tipo');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_propiedad');
    }

    public function contacto()
    {
        return $this->belongsTo(Contacto::class, 'id_contacto');
    }

    protected static function booted()
    {
        // ✅ Escucha el evento 'creating' para asignar la fecha
        static::creating(function (Propiedad $propiedad) {
            $propiedad->fecha_aceptacion = now();
        });
    }
}
