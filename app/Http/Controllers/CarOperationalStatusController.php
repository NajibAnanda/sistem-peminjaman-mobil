<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Car\SetCarOperationalStatus;
use App\Http\Requests\Car\UpdateCarOperationalStatusRequest;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;

final class CarOperationalStatusController extends Controller
{
    public function __invoke(UpdateCarOperationalStatusRequest $request, Car $car, SetCarOperationalStatus $action): RedirectResponse
    {
        $status = $request->operationalStatus();
        $car = $action->handle($car, $status, $request->user());

        return back()->with('success', "Status mobil {$car->brand} {$car->model} ({$car->plate_number}) sekarang {$status->label()}.");
    }
}
