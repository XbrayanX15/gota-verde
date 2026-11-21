<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitacion extends Model
{
    public const VIGENCIA_HORAS = 72;

    protected $table = 'invitaciones';

    protected $fillable = [
        'vivienda_id', 'invitado_por', 'email', 'token', 'estado', 'expira_en', 'aceptada_en',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['expira_en' => 'datetime', 'aceptada_en' => 'datetime'];
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class, 'vivienda_id');
    }

    public function anfitrion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'pendiente' && $this->expira_en->isFuture();
    }
}
