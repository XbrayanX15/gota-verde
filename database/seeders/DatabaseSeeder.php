<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Catálogos necesarios para que la aplicación funcione.
        $this->call([
            ActividadInicialSeeder::class,
            GamificacionSeeder::class,
        ]);

        // Usuario de prueba solo en desarrollo local.
        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => 'password'],
            );
        }
    }
}
