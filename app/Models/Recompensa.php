<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recompensa extends Model
{
    protected $table = 'recompensas';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'puntos_requeridos', 'icono'];

    protected function casts(): array
    {
        return ['puntos_requeridos' => 'integer'];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recompensa_usuario', 'recompensa_id', 'user_id')
            ->withPivot('otorgada_en')
            ->withTimestamps();
    }
}
