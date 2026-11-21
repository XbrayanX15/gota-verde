<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Racha extends Model
{
    protected $table = 'rachas';

    protected $fillable = ['user_id', 'vivienda_id', 'actual', 'mejor', 'ultima_fecha'];

    protected function casts(): array
    {
        return ['actual' => 'integer', 'mejor' => 'integer', 'ultima_fecha' => 'date'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class, 'vivienda_id');
    }
}
