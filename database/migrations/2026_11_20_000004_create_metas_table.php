<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vivienda_id')->constrained('viviendas')->cascadeOnDelete();
            $table->foreignId('creada_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actividad_id')->nullable()->constrained('actividades')->restrictOnDelete();
            $table->string('tipo', 20); // cuantitativa | cualitativa
            $table->string('titulo', 120);
            // Cuantitativa: litros que se quieren ahorrar en el periodo.
            $table->decimal('objetivo_litros', 12, 2)->nullable();
            // Cualitativa: cantidad máxima por registro (p. ej. duchas de 5 min) y días a cumplir.
            $table->decimal('limite_cantidad', 10, 2)->nullable();
            $table->unsignedSmallInteger('dias_objetivo')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('progreso', 5, 2)->default(0); // porcentaje 0-100
            $table->string('estado', 20)->default('activa'); // activa | cumplida | vencida
            $table->timestamp('cumplida_en')->nullable();
            $table->timestamps();
            $table->index(['vivienda_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
