<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderAddonRequest;
use App\Http\Requests\UpdateOrderAddonRequest;
use App\Models\Order;
use App\Models\OrderAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderAddonController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->orderAddons()->orderBy('id')->get()]);
    }

    public function store(StoreOrderAddonRequest $request, Order $order): JsonResponse
    {
        $addons = DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $validated = $request->validated();
            $entries = $validated['addons'] ?? [
                Arr::only($validated, ['name', 'price']),
            ];

            return collect($entries)
                ->map(fn (array $attributes): OrderAddon => $lockedOrder->orderAddons()->create($attributes));
        });

        return response()->json(['data' => $addons], 201);
    }

    public function update(
        UpdateOrderAddonRequest $request,
        Order $order,
        int $addon,
    ): JsonResponse {
        $orderAddon = DB::transaction(function () use ($request, $order, $addon): OrderAddon {
            Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $orderAddon = $order->orderAddons()->lockForUpdate()->findOrFail($addon);
            $orderAddon->update($request->validated());

            return $orderAddon->refresh();
        });

        return response()->json(['data' => $orderAddon]);
    }

    public function destroy(Order $order, int $addon): JsonResponse
    {
        DB::transaction(function () use ($order, $addon): void {
            Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $orderAddon = $order->orderAddons()->lockForUpdate()->findOrFail($addon);
            $orderAddon->delete();
        });

        return response()->json(status: 204);
    }
}
