<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatusTramite extends Model
{
    use HasFactory;

    protected $table = 'estatus_tramites';

    protected $fillable = ['nombre'];

    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'id_estatus');
    }
}
