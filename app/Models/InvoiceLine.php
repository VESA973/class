<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $fillable = ['position', 'description', 'quantity', 'unit_price_ht', 'vat_rate', 'total_ht'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_ht' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'total_ht' => 'decimal:2',
        ];
    }
}
