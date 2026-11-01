<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'base_price',
    'estimated_duration_minutes',
])]
class Treatment extends Model
{
    use HasFactory;

    /**
     * Castings de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
        ];
    }

    /**
     * Citas en las que se planifica o realiza este tratamiento.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

