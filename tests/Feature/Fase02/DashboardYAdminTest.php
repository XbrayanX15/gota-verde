<?php

use App\Models\Actividad;
use App\Models\RegistroHabito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registroEn($vivienda, User $usuario, float $ahorrados): void
{
    RegistroHabito::create([
        'user_id' => $usuario->id, 'vivienda_id' => $vivienda->id, 'actividad_id' => ducha()->id,
        'fecha' => today(), 'cantidad' => 3, 'litros_consumidos' => 30, 'litros_ahorrados' => $ahorrados,
    ]);
}

it('muestra el dashboard individual de la vivienda activa (RF-12, RF-13)', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario, 'propietario', ['tarifa_m3' => 20]);
    registroEn($vivienda, $usuario, 500);

    // 500 L a $20/m³ = $10.00
    $this->actingAs($usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('500.00 L')
        ->assertSee('$10.00');
});

it('suma todas las viviendas en el dashboard global con la tarifa de cada una (RF-19)', function (): void {
    $usuario = User::factory()->create();
    $casa = viviendaDe($usuario, 'propietario', ['alias' => 'Casa', 'tarifa_m3' => 20]);
    $depa = viviendaDe($usuario, 'miembro', ['alias' => 'Depa', 'tarifa_m3' => 40]);
    $ajena = viviendaDe(User::factory()->create(), 'propietario', ['alias' => 'Ajena']);
    registroEn($casa, $usuario, 1000); // $20
    registroEn($depa, $usuario, 500);  // $20
    registroEn($ajena, User::first(), 9999);

    $this->actingAs($usuario)->get(route('dashboard', ['modo' => 'global']))
        ->assertOk()
        ->assertSee('1,500.00 L')
        ->assertSee('$40.00')
        ->assertDontSee('Ajena');
});

it('el admin administra el catálogo de actividades (CU-16)', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.actividades.store'), [
        'clave' => 'trapeado', 'nombre' => 'Trapear', 'unidad' => 'cubeta',
        'litros_por_unidad' => 10, 'referencia_eficiente' => 1,
    ])->assertRedirect(route('admin.actividades.index'));

    $actividad = Actividad::where('clave', 'trapeado')->sole();
    $this->actingAs($admin)->patch(route('admin.actividades.alternar', $actividad));

    expect($actividad->fresh()->activa)->toBeFalse();
    $this->actingAs(User::factory()->create())->post(route('admin.actividades.store'), [])->assertForbidden();
});

it('renderiza todas las pantallas principales sin errores', function (): void {
    $usuario = User::factory()->admin()->create();
    viviendaDe($usuario);
    ducha();

    foreach (['dashboard', 'habitos.create', 'lecturas.index', 'metas.index', 'logros.index', 'viviendas.index',
        'admin.usuarios.index', 'admin.actividades.index', 'admin.logros.index'] as $ruta) {
        $this->actingAs($usuario)->get(route($ruta))->assertOk();
    }
});
