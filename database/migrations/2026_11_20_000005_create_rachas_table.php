<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rachas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vivienda_id')->constrained('viviendas')->cascadeOnDelete();
            $table->unsignedInteger('actual')->default(0);
            $table->unsignedInteger('mejor')->default(0);
            $table->date('ultima_fecha')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'vivienda_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rachas');
    }
};
