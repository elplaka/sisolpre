<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectorTramite extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'sectores_tramites';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
    ];

    // Si tuvieras otros modelos que hagan referencia a SectorTramite,
    // por ejemplo, si un "Trámite" pertenece a un "SectorTramite",
    // podrías definir relaciones aquí.
    // Por ejemplo, para una relación uno a muchos donde un SectorTramite
    // tiene muchas Solicitudes:
    
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_sector');
    }
}