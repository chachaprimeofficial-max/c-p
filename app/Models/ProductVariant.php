<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'sku', 'attributes', 'stock', 'price_adjustment', 'barcode'];
    protected function casts(): array { return ['attributes' => 'array', 'price_adjustment' => 'decimal:2']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
