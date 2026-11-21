<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaConsumo extends Model
{
    protected $table = 'lecturas_consumo';

    protected $fillable = [
        'vivienda_id', 'user_id', 'tipo', 'periodo_inicio', 'periodo_fin', 'consumo_m3', 'importe',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'consumo_m3' => 'decimal:3',
            'importe' => 'decimal:2',
        ];
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class, 'vivienda_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function litros(): float
    {
        return (float) $this->consumo_m3 * 1000;
    }

    public function dias(): int
    {
        return (int) $this->periodo_inicio->diffInDays($this->periodo_fin) + 1;
    }
}
