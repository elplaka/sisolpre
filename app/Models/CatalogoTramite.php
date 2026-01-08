<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoTramite extends Model
{
    use HasFactory;

    protected $table = 'catalogo_tramites';

    public function tipoTramite()
    {
        return $this->belongsTo(TipoTramite::class, 'id_tipo');
    }

    public function solicitudesTramites()
    {
        return $this->hasMany(SolicitudTramite::class, 'id_tramite');
    }  

    public function requisitos()
    {
        return $this->belongsToMany(
            RequisitoDocumentacion::class,
            'catalogo_tramites_requisitos', // 👈 Nombre de la tabla pivote
            'id_tramite_catalogo',          // Clave foránea local
            'id_requisito'                  // Clave foránea remota
        )->withPivot([
            'obligatorio', 
            'activo', 
            'editable', 
        ])->withTimestamps();
    }
}
