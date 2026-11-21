<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vivienda;

/**
 * RF-05 y RNF-31: permisos dentro de una vivienda.
 * - Miembro: consulta la vivienda y registra hábitos, lecturas y metas.
 * - Propietario: además edita el perfil, invita y gestiona miembros.
 */
class ViviendaPolicy
{
    public function view(User $usuario, Vivienda $vivienda): bool
    {
        return $vivienda->esMiembro($usuario);
    }

    public function registrar(User $usuario, Vivienda $vivienda): bool
    {
        return $vivienda->esMiembro($usuario);
    }

    public function update(User $usuario, Vivienda $vivienda): bool
    {
        return $vivienda->esPropietario($usuario);
    }

    public function delete(User $usuario, Vivienda $vivienda): bool
    {
        return $vivienda->esPropietario($usuario);
    }

    public function gestionarMiembros(User $usuario, Vivienda $vivienda): bool
    {
        return $vivienda->esPropietario($usuario);
    }
}
