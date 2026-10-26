<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceTier extends Model
{
    protected $fillable = ['product_id', 'min_quantity', 'max_quantity', 'unit_price', 'currency', 'label'];
    protected function casts(): array { return ['unit_price' => 'decimal:2']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
