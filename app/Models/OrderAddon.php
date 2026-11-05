<?php

namespace App\Models;

use Database\Factories\OrderAddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'name', 'price'])]
class OrderAddon extends Model
{
    /** @use HasFactory<OrderAddonFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (OrderAddon $orderAddon): void {
            $orderAddon->refreshOrderTotals();
        });

        static::deleted(function (OrderAddon $orderAddon): void {
            $orderAddon->refreshOrderTotals();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'queue_number');
    }

    private function refreshOrderTotals(): void
    {
        Order::query()->findOrFail($this->order_id)->recalculateTotals();
    }
}
