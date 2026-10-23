<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Report\ExportBookings;
use App\Http\Requests\Booking\ExportHistoryRequest;
use Symfony\Component\HttpFoundation\Response;

final class HistoryExportController extends Controller
{
    public function __invoke(ExportHistoryRequest $request, ExportBookings $action): Response
    {
        return $action->handle($request->tableQuery(), $request->exportFormat(), $request->user());
    }
}
