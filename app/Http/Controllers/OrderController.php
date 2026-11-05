<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const APPENDED_PAYMENT_ATTRIBUTES = [
        'total_payment',
        'remaining_payment',
        'payment_status',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateListing($request);
        $orders = $this->listingQuery($validated)
            ->orderBy('queue_number', $validated['sort'] === 'queue_desc' ? 'desc' : 'asc')
            ->paginate($validated['per_page'])
            ->withQueryString();

        $this->appendPaymentAttributes($orders->getCollection());

        $activeEstimate = Order::query()
            ->where('order_status', 'active')
            ->sum('total_price');

        return response()->json([
            'data' => $orders,
            'active_estimate_total' => $this->formatMoney($activeEstimate),
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $validated = $this->validateListing($request);
        $year = $request->validate([
            'year' => ['sometimes', 'integer', 'between:2000,2200'],
        ])['year'] ?? now()->year;

        $query = $this->listingQuery($validated);
        $query->whereYear('order_date', $year);

        $groups = $query
            ->orderBy('order_date')
            ->orderBy('queue_number')
            ->get()
            ->groupBy(fn (Order $order): string => $order->order_date->format('Y-m'));

        $groups->each(fn ($orders) => $this->appendPaymentAttributes($orders));

        return response()->json(['data' => $groups]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request): Order {
            $validated = $request->validated();
            $customer = isset($validated['customer_id'])
                ? Customer::query()->findOrFail($validated['customer_id'])
                : Customer::query()->create($validated['customer']);

            $package = isset($validated['package_id'])
                ? Package::query()->findOrFail($validated['package_id'])
                : null;

            $attributes = Arr::only($validated, [
                'order_date',
                'agreed_start_date',
                'actual_start_date',
                'deadline',
                'notes',
                'work_status',
            ]);
            $attributes['customer_id'] = $customer->getKey();
            $attributes['package_id'] = $package?->getKey();
            $attributes['package_name'] = $package?->name ?? $validated['package_name'];
            $attributes['package_price'] = $validated['package_price'] ?? $package?->default_price ?? '0.00';

            return Order::query()->create($attributes);
        });

        return response()->json(['data' => $this->loadOrder($order)], 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json(['data' => $this->loadOrder($order)]);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $validated = $request->validated();
            $attributes = Arr::only($validated, [
                'order_date',
                'agreed_start_date',
                'actual_start_date',
                'deadline',
                'notes',
                'work_status',
            ]);

            if (isset($validated['customer_id'])) {
                $attributes['customer_id'] = $validated['customer_id'];
            } elseif (isset($validated['customer'])) {
                $attributes['customer_id'] = Customer::query()->create($validated['customer'])->getKey();
            }

            if (array_key_exists('package_id', $validated)) {
                $package = isset($validated['package_id'])
                    ? Package::query()->findOrFail($validated['package_id'])
                    : null;

                $attributes['package_id'] = $package?->getKey();

                if ($package !== null) {
                    $attributes['package_name'] = $package->name;
                    $attributes['package_price'] = $validated['package_price'] ?? $package->default_price;
                } else {
                    $attributes['package_name'] = $validated['package_name'];

                    if (isset($validated['package_price'])) {
                        $attributes['package_price'] = $validated['package_price'];
                    }
                }
            } elseif (array_key_exists('package_name', $validated)) {
                $attributes['package_id'] = null;
                $attributes['package_name'] = $validated['package_name'];
            }

            if (array_key_exists('package_price', $validated)) {
                $attributes['package_price'] = $validated['package_price'];
            }

            $order->update($attributes);

            return $order;
        });

        return response()->json(['data' => $this->loadOrder($order)]);
    }

    public function cancel(Order $order): JsonResponse
    {
        $order = DB::transaction(function () use ($order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $order->update(['order_status' => 'cancelled']);

            return $order;
        });

        return response()->json(['data' => $this->loadOrder($order)]);
    }

    public function destroy(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $order->orderAddons()->delete();
            $order->payments()->delete();
            $order->delete();
        });

        return response()->json(status: 204);
    }

    /**
     * @param  array{search?: string, filter: string, sort: string, per_page: int, payment_status: string, work_status: string, month: ?string}  $filters
     * @return Builder<Order>
     */
    private function listingQuery(array $filters): Builder
    {
        $paymentTotal = Payment::query()
            ->selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('payments.order_id', 'orders.queue_number');

        $query = Order::query()
            ->with(['customer', 'package'])
            ->withSum('payments as total_payment_amount', 'amount')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('queue_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                            $customerQuery->where('account_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['payment_status'] !== 'semua', function (Builder $query) use ($filters, $paymentTotal): void {
                match ($filters['payment_status']) {
                    'belum_bayar' => $query->where($paymentTotal, '<=', 0),
                    'dp' => $query->where($paymentTotal, '>', 0)
                        ->where($paymentTotal, '<', DB::raw('orders.total_price')),
                    'lunas' => $query->where($paymentTotal, '>', 0)
                        ->where($paymentTotal, '>=', DB::raw('orders.total_price')),
                };
            })
            ->when($filters['work_status'] !== 'semua', function (Builder $query) use ($filters): void {
                $query->where('work_status', $filters['work_status']);
            })
            ->when($filters['month'] !== null, function (Builder $query) use ($filters): void {
                [$year, $month] = explode('-', $filters['month']);
                $query->whereYear('order_date', $year)->whereMonth('order_date', $month);
            });

        match ($filters['filter']) {
            'belum_bayar', 'dp', 'lunas' => null,
            'belum_dikerjakan' => $query->where('work_status', 'belum_dikerjakan'),
            'on_progress' => $query->where('work_status', 'on_progress'),
            'selesai' => $query->where('work_status', 'selesai'),
            'deadline' => $query->whereNotNull('deadline')
                ->where('order_status', 'active'),
            'dibatalkan' => $query->where('order_status', 'cancelled'),
            default => $query,
        };

        return $query;
    }

    /**
     * @return array{search?: string, filter: string, sort: string, per_page: int, payment_status: string, work_status: string, month: ?string}
     */
    private function validateListing(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'filter' => [
                'sometimes',
                'in:semua,belum_bayar,dp,lunas,belum_dikerjakan,on_progress,selesai,deadline,dibatalkan',
            ],
            'payment_status' => ['sometimes', 'in:semua,belum_bayar,dp,lunas'],
            'work_status' => ['sometimes', 'in:semua,belum_dikerjakan,on_progress,selesai'],
            'month' => ['sometimes', 'nullable', 'date_format:Y-m'],
            'sort' => ['sometimes', 'in:queue_asc,queue_desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $legacyFilter = $validated['filter'] ?? 'semua';

        return [
            ...$validated,
            'filter' => $legacyFilter,
            'payment_status' => $validated['payment_status']
                ?? (in_array($legacyFilter, ['belum_bayar', 'dp', 'lunas'], true) ? $legacyFilter : 'semua'),
            'work_status' => $validated['work_status']
                ?? (in_array($legacyFilter, ['belum_dikerjakan', 'on_progress', 'selesai'], true) ? $legacyFilter : 'semua'),
            'month' => $validated['month'] ?? null,
            'sort' => $validated['sort'] ?? 'queue_asc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    private function loadOrder(Order $order): Order
    {
        $order->load(['customer', 'package', 'orderAddons', 'payments'])
            ->loadSum('payments as total_payment_amount', 'amount');

        return $order->append(self::APPENDED_PAYMENT_ATTRIBUTES);
    }

    private function appendPaymentAttributes(iterable $orders): void
    {
        foreach ($orders as $order) {
            $order->append(self::APPENDED_PAYMENT_ATTRIBUTES);
        }
    }

    private function formatMoney(mixed $amount): string
    {
        $amount = (string) ($amount ?? '0');

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $parts)) {
            throw new \UnexpectedValueException('The database returned an invalid currency total.');
        }

        return $parts[1].'.'.str_pad($parts[2] ?? '', 2, '0');
    }
}
