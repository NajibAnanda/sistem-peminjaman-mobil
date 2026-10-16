<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Report\ExportBookings;
use App\Http\Requests\Booking\ExportHistoryRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HistoryExportController extends Controller
{
    public function __invoke(ExportHistoryRequest $request, ExportBookings $action): BinaryFileResponse
    {
        return $action->handle($request->tableQuery(), $request->exportFormat(), $request->user());
    }
}
