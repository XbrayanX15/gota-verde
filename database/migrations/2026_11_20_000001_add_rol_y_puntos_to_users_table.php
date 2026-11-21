<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // RF-04: rol global del sistema ('usuario' o 'admin').
            $table->string('rol', 20)->default('usuario')->after('password');
            // Puntos acumulados por la gamificación (RF-11).
            $table->unsignedInteger('puntos')->default(0)->after('rol');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['rol', 'puntos']);
        });
    }
};
