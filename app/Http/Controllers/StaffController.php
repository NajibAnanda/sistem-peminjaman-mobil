<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\CreateStaffAccount;
use App\Actions\Staff\UpdateStaffAccount;
use App\Enums\ActiveStatus;
use App\Enums\StaffRole;
use App\Http\Controllers\Concerns\FlashesTemporaryPassword;
use App\Http\Controllers\Concerns\PaginatesForTable;
use App\Http\Requests\Staff\StaffIndexRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class StaffController extends Controller
{
    use FlashesTemporaryPassword;
    use PaginatesForTable;

    public function index(StaffIndexRequest $request): Response
    {
        $table = $request->tableQuery();

        $staff = $this->paginateForTable(
            User::query()->forTable($table)->orderByDesc('is_active')->orderBy('name'),
        );

        return Inertia::render('staff/index', [
            'staff' => StaffResource::collection($staff),
            'query' => $table->toArray(),
            'roles' => StaffRole::options(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function store(StoreStaffRequest $request, CreateStaffAccount $action): RedirectResponse
    {
        $credentials = $action->handle($request->staffData(), $request->user());

        $this->flashTemporaryPassword($credentials, 'Akun petugas berhasil dibuat.');

        return to_route('staff.index');
    }

    public function update(UpdateStaffRequest $request, User $user, UpdateStaffAccount $action): RedirectResponse
    {
        $action->handle($user, $request->staffData(), $request->user());

        return back()->with('success', 'Akun petugas berhasil diperbarui.');
    }
}
