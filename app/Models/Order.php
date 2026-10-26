<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = ['order_number', 'user_id', 'customer_name', 'email', 'phone', 'currency', 'subtotal', 'shipping_total', 'grand_total', 'payment_provider', 'payment_status', 'order_status', 'payment_reference', 'courier_name', 'tracking_number', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_postal_code', 'shipping_address', 'customer_notes', 'paid_at', 'shipped_at'];
    protected function casts(): array { return ['subtotal' => 'decimal:2', 'shipping_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_at' => 'datetime', 'shipped_at' => 'datetime']; }
    protected static function booted(): void { static::creating(function (Order $order) { if (blank($order->order_number)) $order->order_number = 'CP'.now()->format('ymd').strtoupper(Str::random(8)); }); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function returns(): HasMany { return $this->hasMany(OrderReturn::class); }
}
