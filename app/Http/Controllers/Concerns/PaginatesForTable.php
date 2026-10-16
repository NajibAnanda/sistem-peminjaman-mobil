<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait PaginatesForTable
{
    /**
     * Pagination standar tabel: jumlah baris dari config/fleet.php dan query
     * string dipertahankan di tautan halaman.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    private function paginateForTable(Builder $query): LengthAwarePaginator
    {
        $perPage = (int) config('fleet.per_page');
        $paginator = (clone $query)->paginate($perPage)->withQueryString();

        // Halaman di luar jangkauan (mis. setelah data berkurang atau URL lama)
        // diarahkan ke halaman terakhir agar tidak tampil kosong.
        if ($paginator->isEmpty() && $paginator->currentPage() > $paginator->lastPage()) {
            $paginator = (clone $query)->paginate($perPage, page: $paginator->lastPage())->withQueryString();
        }

        return $paginator;
    }
}
