<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_persona',
        'telefono',
        'email',
        'id_domicilio'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_contacto');
    }

    public function domicilio_notificacion()
    {
        return $this->belongsTo(DomicilioNotificacion::class, 'id_domicilio');
    }
}
