<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitoDocumentacion extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'requisitos_documentacion';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'nombre_corto',
        'editable',
        'activo'
    ];

    /**
     * Los trámites que requieren este requisito.
     */
    public function tramites()
    {
        return $this->belongsToMany(
            CatalogoTramite::class,
            'catalogo_tramites_requisitos', // Nombre de la tabla pivote
            'id_requisito',                 // Clave foránea local en la tabla pivote
            'id_tramite_catalogo'           // Clave foránea del modelo remoto en la tabla pivote
        )->withPivot('obligatorio', 'activo', 'editable')
         ->orderBy('catalogo_tramites.nombre', 'asc')
        ->withTimestamps();
    }

    public function solicitudes()
    {
        // El orden de las claves foráneas debe coincidir con la tabla pivote.
        return $this->belongsToMany(
            Solicitud::class,              // El modelo con el que se relaciona
            'solicitudes_documentacion',   // 1. Nombre explícito de la tabla pivote (importante)
            'id_requisito_documentacion',  // 2. Clave foránea local (ID de este modelo) en la tabla pivote
            'id_solicitud'                 // 3. Clave foránea relacionada (ID del otro modelo) en la tabla pivote
        )
        // Recuerda incluir withPivot() y withTimestamps() si las usas.
        // ->withPivot('entregado')
        ->withTimestamps();
    }
}