<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meta extends Model
{
    public const CUANTITATIVA = 'cuantitativa';

    public const CUALITATIVA = 'cualitativa';

    protected $table = 'metas';

    protected $fillable = [
        'vivienda_id', 'creada_por', 'actividad_id', 'tipo', 'titulo', 'objetivo_litros',
        'limite_cantidad', 'dias_objetivo', 'fecha_inicio', 'fecha_fin', 'progreso', 'estado', 'cumplida_en',
    ];

    protected function casts(): array
    {
        return [
            'objetivo_litros' => 'decimal:2',
            'limite_cantidad' => 'decimal:2',
            'dias_objetivo' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'progreso' => 'decimal:2',
            'cumplida_en' => 'datetime',
        ];
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class, 'vivienda_id');
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }
}
