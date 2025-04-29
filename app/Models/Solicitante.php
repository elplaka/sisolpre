<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitante extends Model
{
    use HasFactory;

    protected $table = 'solicitantes';

    protected $fillable = [
        'id_persona',
        'calle',
        'id_colonia',
        'num_casa',
        'id_localidad',
        'telefono',
        'email'
    ];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_solicitante');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function colonia()
    {
        return $this->belongsTo(Colonia::class, 'id_colonia');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'id_localidad');
    }
}
