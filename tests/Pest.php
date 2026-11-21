<?php

use App\Models\Actividad;
use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Crea una vivienda y vincula al usuario con el rol indicado.
 */
function viviendaDe(User $usuario, string $rol = 'propietario', array $datos = []): Vivienda
{
    $vivienda = Vivienda::create([
        'alias' => 'Casa '.fake()->unique()->word(), 'tipo' => 'Casa', 'habitantes' => 3,
        'alcaldia' => 'Gustavo A. Madero', 'codigo_postal' => '07738', 'tarifa_m3' => 20,
        ...$datos,
    ]);
    $vivienda->usuarios()->attach($usuario->id, ['rol' => $rol]);

    return $vivienda;
}

/**
 * Actividad "Ducha" de prueba: 10 L/min con referencia eficiente de 5 minutos.
 */
function ducha(): Actividad
{
    return Actividad::firstOrCreate(['clave' => 'ducha'], [
        'nombre' => 'Ducha', 'unidad' => 'minuto', 'litros_por_unidad' => 10,
        'referencia_eficiente' => 5, 'activa' => true,
    ]);
}
