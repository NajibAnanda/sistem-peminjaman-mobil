<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Car\ReplaceCarPhoto;
use App\Http\Requests\Car\StoreCarPhotoRequest;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;

final class CarPhotoController extends Controller
{
    public function __invoke(StoreCarPhotoRequest $request, Car $car, ReplaceCarPhoto $action): RedirectResponse
    {
        $action->handle($car, $request->position(), $request->photo(), $request->user());

        return back()->with('success', "Foto mobil {$car->brand} {$car->model} ({$car->plate_number}) berhasil disimpan.");
    }
}
