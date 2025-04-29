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
}
