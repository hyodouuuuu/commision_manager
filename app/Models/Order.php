<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'customer_id',
    'package_id',
    'package_name',
    'package_price',
    'order_date',
    'agreed_start_date',
    'actual_start_date',
    'deadline',
    'notes',
    'work_status',
    'order_status',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $primaryKey = 'queue_number';

    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'order_date' => 'date:Y-m-d',
            'agreed_start_date' => 'date:Y-m-d',
            'actual_start_date' => 'date:Y-m-d',
            'deadline' => 'date:Y-m-d',
            'addon_total' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if ($order->package_id !== null) {
                $package = $order->package ?? Package::query()->findOrFail($order->package_id);

                $order->package_name ??= $package->name;
                $order->package_price ??= $package->default_price;
            }

            $order->addon_total ??= '0.00';
            $order->syncTotalPrice();
        });

        static::saving(function (Order $order): void {
            if ($order->exists) {
                $order->addon_total = self::fromMinorUnits(
                    self::toMinorUnits($order->orderAddons()->sum('price'))
                );
            }

            $order->syncTotalPrice();
        });
    }

    protected function totalPayment(): Attribute
    {
        return Attribute::get(fn (): string => self::fromMinorUnits($this->paymentTotalInMinorUnits()));
    }

    protected function remainingPayment(): Attribute
    {
        return Attribute::get(fn (): string => self::fromMinorUnits(
            max(0, self::toMinorUnits($this->total_price) - $this->paymentTotalInMinorUnits())
        ));
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $paid = $this->paymentTotalInMinorUnits();
            $total = self::toMinorUnits($this->total_price);

            if ($paid === 0) {
                return 'Belum Bayar';
            }

            return $paid < $total ? 'DP' : 'Lunas';
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function orderAddons(): HasMany
    {
        return $this->hasMany(OrderAddon::class, 'order_id', 'queue_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'queue_number');
    }

    public function recalculateTotals(): void
    {
        $totals = DB::transaction(function (): array {
            $order = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $addonTotal = self::toMinorUnits($order->orderAddons()->sum('price'));
            $totalPrice = self::toMinorUnits($order->package_price) + $addonTotal;

            $order->forceFill([
                'addon_total' => self::fromMinorUnits($addonTotal),
                'total_price' => self::fromMinorUnits($totalPrice),
            ])->saveQuietly();

            return [
                'addon_total' => self::fromMinorUnits($addonTotal),
                'total_price' => self::fromMinorUnits($totalPrice),
            ];
        });

        $this->forceFill($totals)->syncOriginal();
    }

    private function paymentTotalInMinorUnits(): int
    {
        if (array_key_exists('total_payment_amount', $this->attributes)) {
            return self::toMinorUnits($this->attributes['total_payment_amount']);
        }

        return self::toMinorUnits($this->payments()->sum('amount'));
    }

    private function syncTotalPrice(): void
    {
        $totalInMinorUnits = self::toMinorUnits($this->package_price ?? '0.00')
            + self::toMinorUnits($this->addon_total ?? '0.00');

        $this->total_price = self::fromMinorUnits($totalInMinorUnits);
    }

    private static function toMinorUnits(mixed $amount): int
    {
        $amount = (string) ($amount ?? '0');

        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $amount, $parts)) {
            throw new \InvalidArgumentException('Money values must be numeric decimal amounts.');
        }

        $whole = (int) $parts[2];
        $fractionalDigits = $parts[3] ?? '';
        $fraction = (int) str_pad(substr($fractionalDigits, 0, 2), 2, '0');
        $minorUnits = ($whole * 100) + $fraction;

        if (isset($fractionalDigits[2]) && $fractionalDigits[2] >= '5') {
            $minorUnits++;
        }

        return ($parts[1] ?? '') === '-' ? -$minorUnits : $minorUnits;
    }

    private static function fromMinorUnits(int $amount): string
    {
        $absoluteAmount = abs($amount);
        $sign = $amount < 0 ? '-' : '';

        return sprintf('%s%d.%02d', $sign, intdiv($absoluteAmount, 100), $absoluteAmount % 100);
    }
}
