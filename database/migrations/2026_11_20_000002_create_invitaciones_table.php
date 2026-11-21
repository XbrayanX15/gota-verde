<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vivienda_id')->constrained('viviendas')->cascadeOnDelete();
            $table->foreignId('invitado_por')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            // Se guarda el hash SHA-256 del token, nunca el token en claro.
            $table->string('token', 64)->unique();
            $table->string('estado', 20)->default('pendiente'); // pendiente | aceptada | expirada | cancelada
            $table->timestamp('expira_en');
            $table->timestamp('aceptada_en')->nullable();
            $table->timestamps();
            $table->index(['vivienda_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones');
    }
};
