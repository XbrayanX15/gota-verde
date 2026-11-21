<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logros', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255);
            // Criterio: registros | racha | litros_ahorrados | metas_cumplidas
            $table->string('criterio', 30);
            $table->unsignedInteger('umbral');
            $table->unsignedInteger('puntos')->default(0);
            $table->string('icono', 10)->default('🏅');
            $table->timestamps();
        });

        Schema::create('logro_usuario', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('logro_id')->constrained('logros')->cascadeOnDelete();
            $table->timestamp('obtenido_en');
            $table->timestamps();
            $table->unique(['user_id', 'logro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logro_usuario');
        Schema::dropIfExists('logros');
    }
};
