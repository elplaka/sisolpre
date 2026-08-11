<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted()
    {
        // Antes de que se inserte un nuevo registro en la base de datos...
        static::creating(function ($tramite) {
            // Genera el slug automáticamente basándose en el nombre
            $tramite->slug = Str::slug($tramite->nombre);
        });
    }
}
