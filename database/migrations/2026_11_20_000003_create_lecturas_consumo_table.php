<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas_consumo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vivienda_id')->constrained('viviendas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 20); // recibo | medidor
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->decimal('consumo_m3', 10, 3);
            $table->decimal('importe', 10, 2)->nullable();
            $table->timestamps();
            $table->index(['vivienda_id', 'periodo_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_consumo');
    }
};
