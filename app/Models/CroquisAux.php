<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CroquisAux extends Model
{
    use HasFactory;

    protected $table = 'croquis_aux';

     protected $fillable = [
            'id_solicitud',
            'img',
        ];

    /**
     * Get the solicitud that owns the referencia.
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
