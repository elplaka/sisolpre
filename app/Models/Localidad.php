<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Localidad extends Model
{
    use HasFactory;

    protected $table = 'localidades';

    protected $fillable = ['id', 'nombre'];

    public function propiedades()
    {
        return $this->hasMany(Propiedad::class, 'id_localidad');
    }

    public function solicitudesViaPropiedad()
    {
        return $this->hasManyThrough(
            Solicitud::class,       // El modelo final que quieres obtener
            Propiedad::class,       // El modelo intermedio (a través del cual pasas)
            'id_localidad',         // Clave foránea en el modelo intermedio (Propiedad)
            'id_propiedad'          // Clave foránea en el modelo final (Solicitud)
        );
    }

    public function referencias()
    {
        return $this->hasMany(SolicitudReferencia::class, 'id_localidad');
    }

    public function scopeSearch(Builder $query, ?string $termino): Builder
    {
        if (empty($termino)) {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'LIKE', "%{$termino}%");
        });
    }
}
