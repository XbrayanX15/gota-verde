<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Logro extends Model
{
    /** Criterios con los que el servicio de gamificación evalúa cada logro. */
    public const CRITERIOS = [
        'registros' => 'Hábitos registrados',
        'racha' => 'Días de racha',
        'litros_ahorrados' => 'Litros ahorrados (acumulado)',
        'metas_cumplidas' => 'Metas cumplidas',
    ];

    protected $table = 'logros';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'criterio', 'umbral', 'puntos', 'icono'];

    protected function casts(): array
    {
        return ['umbral' => 'integer', 'puntos' => 'integer'];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'logro_usuario', 'logro_id', 'user_id')
            ->withPivot('obtenido_en')
            ->withTimestamps();
    }
}
