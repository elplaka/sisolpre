<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Colonia extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

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
