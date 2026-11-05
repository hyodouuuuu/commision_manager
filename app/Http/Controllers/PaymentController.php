<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        $order->loadSum('payments as total_payment_amount', 'amount');

        return response()->json([
            'data' => $order->payments()->orderBy('payment_date')->orderBy('id')->get(),
            'total_payment' => $order->total_payment,
            'remaining_payment' => $order->remaining_payment,
            'payment_status' => $order->payment_status,
        ]);
    }

    public function store(StorePaymentRequest $request, Order $order): JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $order): Payment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            return $lockedOrder->payments()->create($request->validated());
        });
        $order = $order->fresh()->loadSum('payments as total_payment_amount', 'amount');

        return response()->json([
            'data' => $payment,
            'total_payment' => $order->total_payment,
            'remaining_payment' => $order->remaining_payment,
            'payment_status' => $order->payment_status,
        ], 201);
    }

    public function update(
        UpdatePaymentRequest $request,
        Order $order,
        int $payment,
    ): JsonResponse {
        $payment = DB::transaction(function () use ($request, $order, $payment): Payment {
            Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $transaction = $order->payments()->lockForUpdate()->findOrFail($payment);
            $transaction->update($request->validated());

            return $transaction->refresh();
        });

        $order = $order->fresh()->loadSum('payments as total_payment_amount', 'amount');

        return response()->json([
            'data' => $payment,
            'total_payment' => $order->total_payment,
            'remaining_payment' => $order->remaining_payment,
            'payment_status' => $order->payment_status,
        ]);
    }

    public function destroy(Order $order, int $payment): JsonResponse
    {
        DB::transaction(function () use ($order, $payment): void {
            Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $order->payments()->lockForUpdate()->findOrFail($payment)->delete();
        });

        return response()->json(status: 204);
    }
}
