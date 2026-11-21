<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Permite crear el primer administrador sin tocar la base de datos a mano:
 *   php artisan gotaverde:admin correo@ejemplo.com
 */
class AsignarAdministrador extends Command
{
    protected $signature = 'gotaverde:admin {email : Correo de una cuenta ya registrada} {--quitar : Regresa la cuenta al rol usuario}';

    protected $description = 'Asigna o retira el rol de administrador a una cuenta';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (! $usuario) {
            $this->error('No existe una cuenta con ese correo.');

            return self::FAILURE;
        }

        $usuario->forceFill(['rol' => $this->option('quitar') ? User::ROL_USUARIO : User::ROL_ADMIN])->save();
        $this->info("{$usuario->email} ahora tiene el rol: {$usuario->rol}");

        return self::SUCCESS;
    }
}
