<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudHistorial extends Model
{
    protected $table = 'solicitudes_historial';

    protected $fillable = [
        'solicitud_id',
        'id_user',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
    ];

    // Esto convierte automáticamente los campos JSON a arrays de PHP y viceversa
    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    // Relación opcional para saber a qué usuario pertenece este registro del historial
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function historials()
    {
        return $this->hasMany(SolicitudHistorial::class, 'solicitud_id');
    }
}
