<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Support\Search\GlobalSearch;
use Illuminate\Http\JsonResponse;

/**
 * Data untuk command palette (Ctrl+K). Satu-satunya endpoint JSON aplikasi:
 * hasil diambil sambil mengetik tanpa berpindah halaman.
 */
final class GlobalSearchController extends Controller
{
    public function __invoke(GlobalSearchRequest $request, GlobalSearch $search): JsonResponse
    {
        return response()->json([
            'groups' => $search->run($request->user(), $request->term()),
        ]);
    }
}
