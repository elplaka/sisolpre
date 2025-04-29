<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatusSolicitud extends Model
{
    use HasFactory;

    protected $table = 'estatus_solicitudes';

    protected $fillable = ['nombre'];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_estatus');
    }

}
