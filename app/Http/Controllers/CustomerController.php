<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = Customer::query()
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('account_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderBy('account_name')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->create($request->validated());

        return response()->json(['data' => $customer], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $orders = $customer->orders()
            ->with(['package'])
            ->withSum('payments as total_payment_amount', 'amount')
            ->orderByDesc('queue_number')
            ->paginate(15);

        $orders->getCollection()->each(fn (Order $order) => $order->append([
            'total_payment',
            'remaining_payment',
            'payment_status',
        ]));

        return response()->json([
            'data' => $customer,
            'orders' => $orders,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json(['data' => $customer->refresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $deleted = DB::transaction(function () use ($customer): bool {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->getKey());

            if ($lockedCustomer->orders()->exists()) {
                return false;
            }

            $lockedCustomer->delete();

            return true;
        });

        if (! $deleted) {
            return response()->json([
                'message' => 'Customer cannot be deleted while orders are attached.',
            ], 409);
        }

        return response()->json(status: 204);
    }
}
