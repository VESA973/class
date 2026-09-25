<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un parametre global (cle / valeur JSON). Utiliser App\Services\Settings plutot que ce modele directement. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
