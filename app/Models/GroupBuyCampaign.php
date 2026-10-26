<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupBuyCampaign extends Model
{
    protected $fillable = ['product_id', 'title', 'unit_price', 'currency', 'target_participants', 'current_participants', 'starts_at', 'ends_at', 'status'];
    protected function casts(): array { return ['unit_price' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
