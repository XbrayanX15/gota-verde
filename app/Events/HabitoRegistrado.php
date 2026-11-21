<?php

namespace App\Events;

use App\Models\RegistroHabito;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DS-02: se dispara después de guardar un hábito y activa la gamificación.
 */
class HabitoRegistrado
{
    use Dispatchable, SerializesModels;

    public function __construct(public RegistroHabito $registro) {}
}
