<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Car\CreateCar;
use App\Actions\Car\UpdateCar;
use App\Enums\CarDisplayStatus;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\Car\CarIndexRequest;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Http\Resources\BookingSummaryResource;
use App\Http\Resources\CarResource;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class CarController extends Controller
{
    use PaginatesForTable;

    public function index(CarIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        $cars = $this->paginateForTable(
            Car::query()
                ->withDisplayStatus()
                ->with('photos')
                ->forTable($table)
                ->orderByDesc('is_active')
                ->orderBy('plate_number'),
        );

        return Inertia::render('cars/index', [
            'cars' => CarResource::collection($cars),
            'query' => $table->toArray(),
            'statuses' => CarDisplayStatus::options(),
        ]);
    }

    public function store(StoreCarRequest $request, CreateCar $action): RedirectResponse
    {
        $car = $action->handle($request->carData(), $request->user());

        return to_route('cars.show', $car)->with('success', 'Mobil berhasil disimpan. Silakan unggah foto referensi.');
    }

    public function show(Car $car): Response
    {
        Gate::authorize('view', $car);

        $car = Car::query()
            ->withDisplayStatus()
            ->with(['photos', 'activeBooking.employee', 'scheduledBookings.employee'])
            ->findOrFail($car->id);

        // resolve(): props berupa objek/array biasa, tanpa pembungkus "data".
        return Inertia::render('cars/show', [
            'car' => (new CarResource($car))->resolve(),
            'activeBooking' => $car->activeBooking === null
                ? null
                : (new BookingSummaryResource($car->activeBooking))->resolve(),
            'scheduledBookings' => BookingSummaryResource::collection($car->scheduledBookings)->resolve(),
            'maxUploadKb' => (int) config('fleet.photos.max_upload_kb'),
        ]);
    }

    public function update(UpdateCarRequest $request, Car $car, UpdateCar $action): RedirectResponse
    {
        $action->handle($car, $request->carData(), $request->user());

        return back()->with('success', 'Data mobil berhasil diperbarui.');
    }
}
