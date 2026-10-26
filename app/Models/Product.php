<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_code', 'name', 'slug', 'description', 'specification', 'brand_name',
        'country_of_origin', 'category_id', 'sub_category_id', 'sub_sub_category_id',
        'base_price', 'list_price', 'retail_price', 'base_currency', 'b2b_enabled', 'group_buy_enabled',
        'stock', 'selling_countries', 'upc', 'gtin', 'sku', 'qr_code_number',
        'product_weight_lb', 'package_weight_lb', 'liters', 'package_dimensions', 'attributes',
        'has_variations', 'shipping_template', 'shipping_template_id', 'handling_time_days', 'delivery_method',
        'supplier_name', 'supplier_contact', 'tracking_numbers', 'courier_name',
        'return_address', 'return_policy', 'warranty', 'status', 'featured',
    ];

    protected function casts(): array
    {
        return [
            'selling_countries' => 'array', 'attributes' => 'array', 'tracking_numbers' => 'array',
            'b2b_enabled' => 'boolean', 'group_buy_enabled' => 'boolean', 'has_variations' => 'boolean',
            'featured' => 'boolean', 'base_price' => 'decimal:2', 'list_price' => 'decimal:2',
            'retail_price' => 'decimal:2', 'product_weight_lb' => 'decimal:3',
            'package_weight_lb' => 'decimal:3', 'liters' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->slug)) $product->slug = Str::slug($product->name).'-'.Str::lower(Str::random(5));
            if (blank($product->product_code)) $product->product_code = 'CP-'.strtoupper(Str::random(10));
            if (blank($product->sku)) $product->sku = 'SKU-'.strtoupper(Str::random(10));
            if (blank($product->qr_code_number)) $product->qr_code_number = 'QR-'.strtoupper(Str::random(12));
        });
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function shippingTemplate(): BelongsTo { return $this->belongsTo(ShippingTemplate::class); }
    public function media(): HasMany { return $this->hasMany(ProductMedia::class)->orderBy('sort_order'); }
    public function priceTiers(): HasMany { return $this->hasMany(ProductPriceTier::class)->orderBy('min_quantity'); }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function groupBuyCampaigns(): HasMany { return $this->hasMany(GroupBuyCampaign::class); }
}
