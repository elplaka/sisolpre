<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstatusSolicitud extends Model
{
    protected $table = 'estatus_solicitudes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'id_estatus');
    }
}
