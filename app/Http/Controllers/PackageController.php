<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $packages = Package::query()
            ->when($validated['search'] ?? null, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(array_key_exists('is_active', $validated), fn (Builder $query) => $query->where('is_active', $validated['is_active']))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($packages);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::query()->create($request->validated());

        return response()->json(['data' => $package], 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json(['data' => $package]);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $package->update($request->validated());

        return response()->json(['data' => $package->refresh()]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $deleted = DB::transaction(function () use ($package): bool {
            $lockedPackage = Package::query()->lockForUpdate()->findOrFail($package->getKey());

            if ($lockedPackage->orders()->exists()) {
                return false;
            }

            $lockedPackage->delete();

            return true;
        });

        if (! $deleted) {
            return response()->json([
                'message' => 'Package cannot be deleted while orders are attached.',
            ], 409);
        }

        return response()->json(status: 204);
    }
}
