<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Car\DeactivateCar;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CarDeactivationController extends Controller
{
    public function __invoke(Request $request, Car $car, DeactivateCar $action): RedirectResponse
    {
        $car = $action->handle($car, $request->user());

        return back()->with('success', "Mobil {$car->brand} {$car->model} ({$car->plate_number}) dinonaktifkan.");
    }
}
