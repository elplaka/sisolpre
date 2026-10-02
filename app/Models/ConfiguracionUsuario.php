<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionUsuario extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_usuarios';

    // ¡IMPORTANTE! Especifica que 'id_user' es la clave primaria
    protected $primaryKey = 'id_user';

    // ¡IMPORTANTE! Indica que la clave primaria NO es auto-incremental
    public $incrementing = false;

    protected $fillable = [
        'id_user',
        'id_rango_fecha_busqueda'
    ];
}
