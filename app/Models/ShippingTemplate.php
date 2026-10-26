<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'country_codes', 'currency', 'base_rate', 'per_lb_rate', 'handling_days', 'delivery_method', 'is_active'];
    protected function casts(): array { return ['country_codes' => 'array', 'base_rate' => 'decimal:2', 'per_lb_rate' => 'decimal:2', 'is_active' => 'boolean']; }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
