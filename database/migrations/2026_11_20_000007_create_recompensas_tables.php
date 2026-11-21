<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recompensas', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255);
            $table->unsignedInteger('puntos_requeridos');
            $table->string('icono', 10)->default('🎁');
            $table->timestamps();
        });

        Schema::create('recompensa_usuario', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recompensa_id')->constrained('recompensas')->cascadeOnDelete();
            $table->timestamp('otorgada_en');
            $table->timestamps();
            $table->unique(['user_id', 'recompensa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recompensa_usuario');
        Schema::dropIfExists('recompensas');
    }
};
