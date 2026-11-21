<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('bloquea el acceso 60 segundos después de cinco intentos fallidos (CU-02 4b)', function (): void {
    $this->freezeTime();
    $usuario = User::factory()->create(['email' => 'ana@example.test']);

    foreach (range(1, 5) as $intento) {
        $this->post(route('login.store'), ['email' => 'ana@example.test', 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
    }

    // Aun con la contraseña correcta, el sexto intento queda bloqueado.
    $this->post(route('login.store'), ['email' => 'ana@example.test', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Demasiados intentos fallidos. Intenta de nuevo en 60 segundos.']);
    $this->assertGuest();

    $this->travel(61)->seconds();
    $this->post(route('login.store'), ['email' => 'ana@example.test', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($usuario);
});

it('envía el enlace de recuperación y permite restablecer la contraseña (RF-03)', function (): void {
    Notification::fake();
    $usuario = User::factory()->create(['email' => 'luis@example.test']);

    $this->post(route('password.email'), ['email' => 'luis@example.test'])->assertSessionHas('exito');

    $token = null;
    Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $notificacion) use (&$token): bool {
        $token = $notificacion->token;

        return true;
    });

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'luis@example.test',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('NuevaClave123', $usuario->fresh()->password))->toBeTrue();
});

it('no revela si un correo está registrado al pedir recuperación', function (): void {
    $this->post(route('password.email'), ['email' => 'nadie@example.test'])
        ->assertSessionHas('exito')
        ->assertSessionHasNoErrors();
});

it('no permite volverse administrador desde el formulario de registro', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Intruso', 'email' => 'intruso@example.test',
        'password' => 'ClaveSegura123', 'password_confirmation' => 'ClaveSegura123',
        'rol' => 'admin', 'puntos' => 99999,
    ]);

    $usuario = User::where('email', 'intruso@example.test')->first();
    expect($usuario->rol)->toBe('usuario')->and($usuario->puntos)->toBe(0);
});

it('restringe el panel de administración al rol admin (RF-04)', function (): void {
    $this->actingAs(User::factory()->create())->get(route('admin.usuarios.index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.usuarios.index'))->assertOk();
});

it('permite al admin cambiar roles pero no el suyo', function (): void {
    $admin = User::factory()->admin()->create();
    $otro = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.usuarios.rol', $otro), ['rol' => 'admin'])->assertSessionHas('exito');
    expect($otro->fresh()->esAdmin())->toBeTrue();

    $this->actingAs($admin)->patch(route('admin.usuarios.rol', $admin), ['rol' => 'usuario'])->assertSessionHasErrors('rol');
    expect($admin->fresh()->esAdmin())->toBeTrue();
});

it('asigna un administrador con el comando de consola', function (): void {
    $usuario = User::factory()->create(['email' => 'jefe@example.test']);

    $this->artisan('gotaverde:admin', ['email' => 'jefe@example.test'])->assertSuccessful();

    expect($usuario->fresh()->esAdmin())->toBeTrue();
});
